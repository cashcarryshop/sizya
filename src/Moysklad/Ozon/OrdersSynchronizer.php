<?php
/**
 * Синхронизатор остатков МойСклад->Ozon
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Moysklad\Ozon;

use CashCarryShop\Synchronizer\SynchronizerSourceInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Sizya\Synchronizer\AbstractSynchronizer;
use CashCarryShop\Sizya\Synchronizer\RelationRepositoryInterface;;
use CashCarryShop\Sizya\Moysklad\Orders as MoyskladOrders;
use CashCarryShop\Sizya\Moysklad\Utils;;
use CashCarryShop\Sizya\Ozon\Orders as OzonOrders;
use CashCarryShop\Sizya\Events\Error;
use CashCarryShop\Sizya\Events\Success;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Coroutine;
use Psr\Http\Message\ResponseInterface;
use Respect\Validation\Validator as v;
use DateTimeZone;

/**
 * Синхронизатор остатков МойСклад->Ozon
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class OrdersSynchronizer extends AbstractSynchronizer
{
    /**
     * Проверить, поддерживается ли источник
     *
     * @param SynchronizerSourceInterface $source Источник
     *
     * @return bool
     */
    public function supportsSource(SynchronizerSourceInterface $source): bool
    {
        return $source instanceof MoyskladOrders;
    }

    /**
     * Проверить, поддерживается ли цель
     *
     * @param SynchronizerTargetInterface $target Цель
     *
     * @return bool
     */
    public function supportsTarget(SynchronizerTargetInterface $target): bool
    {
        return $target instanceof OzonOrders;
    }

    /**
     * Перехватить ошибку и вызвать событие Error
     *
     * @param PromiseInterface $promise Promise
     *
     * @return PromiseInterface
     */
    protected function eventOtherwise(PromiseInterface $promise): PromiseInterface
    {
        $promise->otherwise(function ($reason) {
            $this->event(new Error($reason));
            $this->running = false;
        });

        return $promise;
    }

    /**
     * Вызвать событие Success на выполнение Promise
     *
     * @param PromiseInterface $promise Promise
     *
     * @return void
     */
    protected function eventSuccess(PromiseInterface $promise): void
    {
        $promise->then(function ($result) {
            $this->running = false;
            $this->event(new Success($result));
        });
    }

    /**
     * Получить заказы по атрибуту, в котором
     * содержиться артикул заказа
     *
     * @param string $attribute Идентификатор атрибута
     * @param array  $postings  Отправления
     *
     * @return PromiseInterface
     */
    private function _getByAttribute(
        string $attribute,
        array $postings
    ): PromiseInterface {
        return Utils::unwrapSettle(
            $this->target->getByAttributes(
                array_map(
                    static fn ($posting) => [
                        'attribute' => $attribute,
                        'value' => $posting['posting_number']
                    ],
                    $postings
                )
            )
        );
    }

    /**
     * Получить калонку атрибутов из полученных заказов
     *
     * Получает калонку attributes, фильтрует атрибуты
     * по переданному идентификатору (поскольку по нему
     * производиться поиск)
     *
     * Возвращает массив со значениями атрибутов,
     * соответственно последовательности
     * переданных заказов
     *
     * @param string $attribute Идентификатор атрибута
     * @param array  $orders    Заказы
     *
     * @return array
     */
    private function _getAttributeColumn(string $attribute, array $orders): array
    {
        return array_map(
            static function ($attributes) use ($attribute) {
                foreach ($attributes as $attr) {
                    if ($attr['id'] === $attribute) {
                        return $attr['value'];
                    }
                }
            },
            array_column($orders, 'attributes')
        );
    }

    /**
     * Действия, в ходе которых используется
     * переданный атрибут
     *
     * Получает заказы из МойСклад по атрибуту.
     *
     * Исходя из переданного массива отправлений Ozon
     * и полученных заказов МойСклад, создает
     * 2 массива: найденные и не найденные заказы.
     *
     * Не Найденные по атрибуту заказы возвращает
     * из Promise.
     *
     * Найденные заказы обновляет, исходя из
     * полученных отправлений Ozon
     *
     * @param array $postings Отправления из Ozon
     * @param array $settings Настройки
     *
     * @return PromiseInterface
     */
    private function _attributeActions(
        array $postings,
        array $settings
    ): PromiseInterface {
        $attribute = $settings['attribute'];
        return $this->_getByAttribute($postings, $attribute)->then(
            function ($responses) use ($postings, $settings, $attribute) {
                foreach ($responses as $response) {
                    $orders = $response->getBody()->toArray()['rows'];
                    $attributes = $this->_getAttributeColumn($attribute, $orders);

                    $found = [];
                    $notFound = [];
                    foreach ($postings as $posting) {
                        $index = array_search($posting['posting_number'], $attributes);

                        if ($index === false) {
                            $notFound[] = $posting;
                            continue;
                        }

                        $order = $orders[$index];
                        $repository->create($posting['posting_number'], $order['id']);
                        $repository->addTargetOptions($order['id'], $order);
                        $repository->addSourceOptions(
                            $posting['posting_number'], $posting
                        );

                        $found[] = $posting;
                    }
                }

                if ($found) {
                    return $this->_updateOrders($found, $settings)
                        ->then(static fn () => $notFound);
                }

                return $notFound;
            }
        );
    }

    /**
     * Конвертировать отправления Ozon в данные
     * для создания заказы покупателя МойСклад
     *
     * @param array $postings Отправления
     * @param array $settings Настройки
     *
     * @return array
     */
    private function _convertPostingsToOrders(array $postings, array $settings): array
    {
        $format = 'Y-m-d\TH:i:s\Z';
        $timezone = new DateTimeZone('UTC');

        return array_map(
            static function ($posting) use ($format, $timezone, $settings) {
                $created = Utils::createFromFormat(
                    $format,
                    $posting['in_process_at'],
                    $timezone
                );

                $order = [
                    'moment' => $created,
                    'created' => $created,
                ];

                if ($shipmentDate = $posting['shipment_date'] ?? false) {
                    $order['deliveryPlannedMoment'] = Utils::createFromFormat(
                        $format,
                        $shipmentDate,
                        $timezone
                    );
                }

                if ($attribute = $settings['attribute'] ?? null) {
                    $order['attributes'] = [
                        'id' => $attribute,
                        'value' => $posting['posting_number']
                    ];
                }

                return $order;
            },
            $postings
        );
    }

    /**
     * Создать заказы
     *
     * @param array $postings Отправления из Ozon
     * @param array $settings Переданные настройки для синхронизации
     *
     * @return PromiseInterface
     */
    private function _createOrders(array $postings, array $settings): PromiseInterface
    {
        if (isset($settings['attribute'])) {
            $this->eventOtherwise(
                $promise = $this->_attributeActions($postings, $settings)->then(
                    function ($postings) {
                        if ($postings) {
                            return $this->target->createOrUpdate(
                                $this->_convertPostingsToOrders($postings)
                            );
                        }
                    }
                )
            );
        } else {
            $this->eventOtherwise(
                $promise = $this->target->createOrUpdate(
                    $this->_convertPostingsToOrders($postings)
                )
            );
        }

        $repository = $settings['repository'];
        return $promise->then(function ($results) use ($postings, $repository) {
            if ($results) {
                $index = 0;
                $promises = [];

                while ($items = array_splice($postings, 0, 500)) {
                    $result = $results[$index];

                    if ($result['state'] === PromiseInterface::FULFILLED) {
                        $orders = $result['value']->getBody()->toArray();

                        foreach ($items as $idx => $posting) {
                            $order = $orders[$idx];

                            $repository->create(
                                $posting['posting_number'],
                                $order['id']
                            );
                            $repository->addSourceOptions(
                                $posting['posting_number'], $posting
                            );
                            $repository->addTargetOptions(
                                $order['id'], $order
                            );

                            $positions = array_map(
                                static fn ($product) => [
                                    'article' => $product['offer_id'],
                                    'quantity' => $product['quantity'],
                                    'price' => (float) $product['price'] * 100,
                                    'reserve' => 0
                                ],
                                $posting['products']
                            );

                            if ($positions) {
                                $articles = array_column($positions, 'article');
                                $promise = $this->target->assortment()
                                    ->getByArticles($articles)
                                    ->then(
                                        function ($results) use (
                                            $articles,
                                            $positions
                                        ) {

                                        }
                                    )

                                $this->eventOtherwise(
                                    $promise = $this->target->addPositions(
                                        $order['id'], $positions
                                    )
                                );

                                $promises[] = $promise;
                            }
                        }
                    }

                    ++$index;
                }

                return $this->getPromiseAggregator()->settle($promises);
            }
        });
    }

    /**
     * Получить все данные из репозитория
     *
     * @param string $sourceId Идентификатор источника
     * @param object $repository Репозиторий
     *
     * @return array
     */
    private function _bundleRepositoryData(string $sourceId, object $repository): array
    {
        return [
            $repository = $settings['repositroy'],
            $targetId = $repository->getTargetId($sourceId),
            $targetId ? $repository->getTargetOptions($targetId) : null,
            $targetId ? $repository->getSourceOptions($sourceId) : null
        ];
    }


    /**
     * Распознать guid из meta данных
     *
     * @param array $meta Мета
     *
     * @return string
     */
    private function _parseMetaGuid(array $meta): string
    {
        return array_slice(explode('/', $meta['meta']['href']), -1, 1);
    }

    /**
     * Обновить заказы
     *
     * @param array $postings Отправления
     * @param array $settings Настройки
     *
     * @return PromiseInterface
     */
    private function _updateOrders(
        array $postings,
        array $settings
    ): PromiseInterface {
        $found = [];
        $notFound = [];

        $repository = $settings['repository'];
        foreach ($postings as $posting) {
            list($orderId, $order, $previousPosting) = $this->_bundleRepositoryData(
                $posting['posting_number'], $repository
            );

            if (!$orderId) {
                $notFound[] = $posting;
                continue;
            }


            $found[] = [
                'posting' => $posting,
                'previous' => $previousPosting,
                'orderId' => $orderId,
                'order' => $order
            ];
        }

        if ($notFound) {
            return $this->_createOrders($notFound, $settings)
                ->then(fn () => $this->_updateOrders($found));
        }

        $update = [];
        foreach ($found as $data) {
            $order = [];

            if ($data['posting']['substatus'] !== $data['previous']['substatus']) {
                $state = $settings['state'][$data['posting']['substatus']] ?? null;
                if ($this->_parseMetaGuid($data['order']['state']) !== $state) {
                    $order['state'] = $state;
                }
            }

            $shipmentDate = $data['posting']['shipment_date'];
            if ($shipmentDate !== $data['previous']['shipment_date']) {
                $datetime = Utils::createFromFormat(
                    'Y-m-d\TH:i:s\Z',
                    $shipmentDate,
                    new DateTimeZone('UTC')
                );

                if ($data['order']['deliveryPlannedMoment'] !== $datetime) {
                    $order['deliveryPlannedMoment'] = $datetime;
                }
            }

            if ($order) {
                $order['id'] = $data['orderId'];
                $update[] = $order;
            }
        }

        $this->eventOtherwise($promise = $this->target->createOrUpdate($update));
        return $promise;
    }

    /**
     * Синхронизировать новое отправление
     *
     * @param string $postingNumber Номер отправления
     * @param array  $settings      Настройки
     *
     * @return PromiseInterface
     */
    private function _synchronizeNewPosting(
        array $postingNumber,
        array $settings
    ): PromiseInterface {
        $this->eventOtherwise(
            $promise = $this->source->getById($postingNumber)->then(
                function ($response) use ($settings) {
                    $posting = $response->getBody()->toArray()['result'];
                    return $this->_createOrders([$posting], $settings);
                }
            )
        );

        return $promise;
    }

    /**
     * Синхронизировать изменение статуса отправления
     *
     * @param string $postingNumber Номер отправления
     * @param string $newState      Новый статус
     * @param array  $settings      Настройки
     *
     * @return bool|PromiseInterface
     */
    private function _synchronizeStateChanged(
        string $postingNumber,
        string $newState,
        array $settings
    ): bool|PromiseInterface {
        if (!isset($settings['state'], $settings['state'][$newState])) {
            return false;
        }

        $repository = $settings['repository'];
        list($orderId, $order, $posting) = $this->_bundleRepositoryData(
            $postingNumber, $repository
        );

        if (!$orderId) {
            return $this->_synchronizeNewPosting($postingNumber, $settings);
        }

        $stateId = $settings['state'][$newState];
        $parsedStateGuid = $this->_parseMetaGuid($order['state']);

        if ($parsedStateGuid === $stateId) {
            $repository->addSourceOptions($postingNumber, ['substatus' => $newState]);
            return true;
        }

        $repository->addSourceOptions($postingNumber, ['substatus' => $newState]);
        $repository->addTargetOptions(
            $order['id'], [
                'state' => [
                    'meta' => $this->target->meta()->create(
                        "entity/customerorder/metadata/states/$stateId", 'state'
                    )
                ]
            ]
        );

        $promise = $this->target->update($orderId, ['state' => $stateId]);
        $promise->otherwise(static function () use (
            $repository,
            $postingNumber,
            $posting,
            $orderId,
            $order
        ) {
            $repository->addSourceOptions($postingNumber, [
                'substatus' => $posting['substatus']
            ]);

            $repository->addTargetOptions($orderId, [
                'state' => $order['state']
            ]);
        });

        $this->eventOtherwise($promise);

        return $promise;
    }

    /**
     * Синхронизировать дату отгрузки
     *
     * @param string $postingNumber Номер отправления
     * @param string $newCutoffDate Новая дата отправления
     * @param array  $settings      Настройки
     *
     * @return bool|PromiseInterface
     */
    private function _synchronizeCutoffDateChanged(
        string $postingNumber,
        string $newCutoffDate,
        array $settings
    ): bool|PromiseInterface {
        $bundle = $this->_bundleRepositoryData($postingNumber, $settings);
        list($repository, $orderId, $order, $posting) = $bundle;

        if (!$orderId) {
            return $this->_synchronizeNewPosting($postingNumber, $settings);
        }

        if ($newCutoffDate === $posting['shipment_date']) {
            return true;
        }

        $datetime = Utils::createFromFormat(
            'Y-m-d\TH:i:s\Z',
            $newCutoffDate,
            new DateTimeZone('UTC')
        );

        if ($datetime === $order['deliveryPlannedMoment']) {
            $repository->addSourceOptions($postingNumber, [
                'shipment_date' => $newCutoffDate
            ]);
            return true;
        }

        $repository->addSourceOptions($postingNumber, [
            'shipment_date' => $newCutoffDate
        ]);
        $repository->addTargetOptions($orderId, [
            'deliveryPlannedMoment' => $datetime
        ]);

        $promise = $this->target->update($orderId, [
            'deliveryPlannedMoment' => $datetime
        ]);

        $this->eventOtherwise($promise);
        $promise->otherwise(static function () use (
            $repository,
            $postingNumber,
            $posting,
            $orderId,
            $order
        ) {
            $repository->addSourceOptions($postingNumber, [
                'shipment_date' => $posting['shipment_date']
            ]);

            $repository->addTargetOptions($orderId, [
                'deliveryPlannedMoment' => $order['deliveryPlannedMoment']
            ]);
        });

        return $promise;
    }

    /**
     * Синхронизировать с помощью push уведомлению
     *
     * @param array $settings Настройки
     *
     * @return bool|PromiseInterface
     */
    private function _synchronizeNotification(array $settings): bool|PromiseInterface
    {
        $message = $settings['message'];

        switch ($message['message_type']) {
            case 'TYPE_NEW_POSTING':
                return $this->_synchronizeNewPosting(
                    $message['posting_number'],
                    $settings
                );
            case 'TYPE_STATE_CHANGED':
                return $this->_synchronizeStateChanged(
                    $message['posting_number'],
                    $message['new_state'],
                    $settings
                );
            case 'TYPE_CUTOFF_DATE_CHANGED':
                if ($message['new_cutoff_date'] ?? null) {
                    return $this->_synchronizeCutoffDateChanged(
                        $message['posting_number'],
                        $message['new_cutoff_date'],
                        $settings
                    );
                }

                return false;
            default:
                return false;
        }
    }

    /**
     * Синхронизировать все
     *
     * @param array $settings Настройки
     *
     * @return PromiseInterface
     */
    private function _synchronizeAll(array $settings): PromiseInterface
    {
        $this->eventOtherwise(
            $promise = $this->source->getOrders()->then(
                function ($postings) use ($settings) {
                    $notFound = array_filter(
                        $postings, static function ($posting) {
                            $repository = $settings['repository'];
                            if ($repository->hasTarget($posting['posting_number'])) {
                                return false;
                            }

                            return true;
                        }
                    );

                    return Coroutine::of(
                        function () use ($posting, $notFound, $settings) {
                            yield [
                                (yield $this->_createOrders($notFound, $settings)),
                                (yield $this->_updateOrders($postings, $settings))
                            ];
                        }
                    );
                }
            )
        );

        return $promise;
    }

    /**
     * Синхронизировать
     *
     * @param array $settings Настройки для синхронизации
     *
     * @return bool
     */
    protected function process(array $settings): bool
    {
        v::allOf(
            v::key('repository', v::instance(RelationRepositoryInterface::class)),
            v::key('attribute', v::stringType()->length(36, 36), false),
            v::key('description', v::stringType()->length(0, 255), false),
            v::key('wait', v::boolType(), false),
            v::key(
                'message',
                v::arrayType()->allOf(
                    v::key('message_type', v::stringType()->in([
                        'TYPE_PING',
                        'TYPE_NEW_POSTING',
                        'TYPE_POSTING_CANCELLED',
                        'TYPE_STATE_CHANGED',
                        'TYPE_CUTOFF_DATE_CHANGED',
                        'TYPE_DELIVERY_DATE_CHANGED',
                        'TYPE_CREATE_OR_UPDATE_ITEM',
                        'TYPE_CREATE_ITEM',
                        'TYPE_UPDATE_ITEM',
                        'TYPE_PRICE_INDEX_CHANGED',
                        'TYPE_STOCKS_CHANGED',
                        'TYPE_NEW_MESSAGE',
                        'TYPE_UPDATE_MESSAGE',
                        'TYPE_MESSAGE_READ',
                        'TYPE_CHAT_CLOSED',
                    ])),
                    v::key('seller_id', v::intType()),
                    v::when(
                        v::key('message_type', v::in([
                            'TYPE_NEW_POSTING',
                            'TYPE_POSTING_CANCELLED',
                            'TYPE_STATE_CHANGED',
                            'TYPE_CUTOFF_DATE_CHANGED',
                            'TYPE_DELIVERY_DATE_CHANGED'
                        ])),
                        v::allOf(
                            v::key('posting_number', v::stringType()),
                            v::key('warehouse_id', v::intType())
                        )
                    ),
                    v::when(
                        v::key('message_type', v::equals('TYPE_STATE_CHANGED')),
                        v::key('new_state', v::stringType())
                    ),
                    v::when(
                        v::key('message_type', v::equals('TYPE_CUTOFF_DATE_CHANGED')),
                        v::key('new_cutoff_date', v::stringType()->nullable(), false)
                    )
                ),
                false
            )
        )->assert($settings);

        $promise = $settings['message'] ?? false
            ? $this->_synchronizeByNotification($settings)
            : $this->_synchronizeAll($settings);

        $settings['wait'] ?? false && $promise->wait();

        return true;
    }
}
