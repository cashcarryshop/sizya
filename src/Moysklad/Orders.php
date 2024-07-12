<?php
/**
 * Класс остатков
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Moysklad;

use CashCarryShop\Sizya\Http\Utils;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Coroutine;
use Psr\Http\Message\ResponseInterface;
use Respect\Validation\Validator as v;
use Throwable;

/**
 * Класс с настройками и логикой получения
 * остатков Moysklad
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
final class Orders extends AbstractEntity
{
    /**
     * Объект ассортимента
     *
     * @var Assortment
     */
    private Assortment $_assortment;

    /**
     * Создать экземпляр элемента
     * синхронизации для заказов
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);

        v::allOf(
            ...array_merge(
                [
                    v::key('organization', v::stringType()->length(36, 36)),
                    v::key('agent', v::stringType()->length(36, 36)),
                    v::key('assortment', v::instance(Assortment::class), false),
                    v::key('vatEnabled', v::boolType(), false),
                    v::key('vatIncluded', v::boolType(), false),
                    v::key('applicable', v::boolType(), false),
                    v::key('description', v::stringType()->length(0, 255), false)
                ],
                array_map(
                    static fn ($key) => v::key(
                        $key, v::stringType()->length(36, 36), false
                    ), [
                        'store',
                        'contract',
                        'salesChannel',
                        'state'
                    ]
                )
            )
        )->assert($this->settings);
    }

    /**
     * Получить класс для работы с ассортиментом МойСклад
     *
     * @return Asosrtment
     */
    public function assortment(): Assortment
    {
        if (isset($this->_assortment)) {
            return $this->_assortment;
        }

        if ($assortment = $this->getSettings('assortment')) {
            return $this->_assortment = $assortment;
        }

        $assortment = new Assortment($this->getSettings());
        $assortment->withPoolFactory($this->getPoolFactory());
        return $this->_assortment = $assortment;
    }

    /**
     * Получить заказ по идентификатору
     *
     * @param string $guid GUID заказа
     *
     * @return PromiseInterface
     */
    public function getById(string $guid): PromiseInterface
    {
        return $this->pool()->add(
            $this->builder()
                ->point("entity/customerorder/$guid")
                ->build('GET')
        );
    }

    /**
     * Получить заказы по значениям атрибутов
     *
     * Принимает массив:
     * ```php
     * $attributes = [
     *    [
     *        'attribute' => '7944ef04-f831-11e5-7a69-971500188b19',
     *        'value'     => 'value'
     *    ]
     *    // ...
     * ];
     * ```
     *
     * @param array $attributes Аттрибуты
     *
     * @return PromiseInterface
     */
    public function getByAttributes(array $attributes): PromiseInterface
    {
        v::arrayType()->length(1)->each(
            v::keySet(
                v::key('attribute', v::stringType()->length(36, 36)),
                v::key('value')
            )
        )->assert($attributes);

        $builder = $this->builder()->point("entity/customerorder");

        $promises = [];
        foreach (array_chunk($attributes, 40) as $chunk) {
            $clone = clone $builder;

            foreach ($chunk as $filter) {
                $attribute = $this->meta()->href(
                    'entity/customerorder/metadata/attributes/'
                        . $filter['attribute']
                );

                $clone->filter($attribute, $filter['value']);
            }

            $promises[] = $this->pool()->add($clone->build('GET'));;
        }

        return $this->getPromiseAggregator()->settle($promises);
    }

    /**
     * Получить заказ покупателя по значению атрибута
     *
     * @param string $guid  GUID атрибута
     * @param mixed  $value Значение
     *
     * @return PromiseInterface
     */
    public function getByAttribute(string $guid, mixed $value): PromiseInterface
    {
        return Utils::unwrapSingleSettle(
            $this->getByAttributes([[
                'attribute' => $guid,
                'value' => $value
            ]])
        );
    }

    /**
     * Установить значение по-умолчанию из "настроек"
     *
     * @param string $key  Ключ массива
     * @param array  $data Ссылка на данные
     *
     * @return void
     */
    private function _setDefault(string $key, array &$data): void
    {
        if (isset($data[$key])) {
            return;
        }

        if ($value = $this->getSettings($key)) {
            $data[$key] = $value;
        }
    }

    /**
     * Установить meta данные по ключу
     *
     * Если данных по ключу нет, пытается получить
     * по этому ключу "настройку" и установить
     * её значение в данные, а потом конвертировать
     * в meta
     *
     * Если данные есть, они должны быть идентификатором
     * для meta, потому-что будет попытка их в это meta
     * преобразовать
     *
     * @param string $key  Ключ массива и название meta
     * @param array  $data Ссылка на данные
     *
     * @return void
     */
    private function _setMeta(string $key, array &$data): void
    {
        static $meta;

        $meta ??= $this->meta();

        if (isset($data[$key])) {
            $data[$key] = ['meta' => $meta->$key($data[$key])];
        }
    }

    /**
     * Создать заказы
     *
     * @param array $orders Заказы
     *
     * @return PromiseInterface
     */
    public function createOrUpdate(array $orders): PromiseInterface
    {
        v::each(
            v::allOf(
                ...array_merge(
                    array_map(
                        static fn ($key) => v::key(
                            $key, v::stringType()->length(36, 36), false
                        ), [
                            'id',
                            'organization',
                            'agent',
                            'contract',
                            'salesChannel',
                            'state'
                        ]
                    ),
                    v::key(
                        'positions', v::each(
                            v::allOf(
                                v::key(
                                    'type', v::stringType()->in(['variant', 'product'])
                                ),
                                v::key('id', v::stringType()->length(36, 36)),
                                v::key('quantity', v::intType(), false),
                                v::key('price', v::floatType(), false),
                                v::key('vat', v::intType(), false),
                                v::key('reserve', v::intType(), false),
                                v::key('discount', v::intType(), false)
                            )
                        ), false
                    ),
                    v::key(
                        'attributes', v::each(
                            v::allOf(
                                v::key('id', v::stringType()->length(36, 36)),
                                v::key('value')
                            )
                        ), false
                    )
                )
            )
        )->assert($orders);

        $builder = $this->builder()->point('entity/customerorder');
        $meta = $this->meta();
        $promises = [];

        foreach (array_chunk($orders, 500) as $chunk) {
            $orders = [];
            $clone = clone $builder;

            foreach ($chunk as $order) {
                if (!isset($order['id'])) {
                    $this->_setDefault('organization', $order);
                    $this->_setDefault('agent', $order);
                    $this->_setDefault('store', $order);
                    $this->_setDefault('contract', $order);
                    $this->_setDefault('salesChannel', $order);
                    $this->_setDefault('state', $order);
                    $this->_setDefault('vatEnabled', $order);
                    $this->_setDefault('vatIncluded', $order);
                    $this->_setDefault('applicable', $order);
                    $this->_setDefault('description', $order);
                }

                if (isset($order['id'])) {
                    $order['meta'] = $meta->customerorder($order['id']);
                    unset($order['id']);
                }

                $this->_setMeta('organization', $order);
                $this->_setMeta('agent', $order);
                $this->_setMeta('store', $order);
                $this->_setMeta('contract', $order);
                $this->_setMeta('salesChannel', $order);

                if (isset($order['state'])) {
                    $order['state'] = $meta->create(
                        "entity/customerorder/metadata/states/{$order['state']}",
                        'state'
                    );
                }

                if (isset($order['positions'])) {
                    $order['positions'] = array_map(
                        static function ($position) {
                            $position['assortment'] = [
                                'meta' => $meta->{$position['type']}($position['id'])
                            ];
                            unset($position['id'], $position['type']);
                            return $position;
                        },
                        $order['positions']
                    );
                }

                $orders[] = $order;
            }

            $promises[] = $this->pool()->add($clone->body($orders)->build('POST'));
        }

        return $this->getPromiseAggregator()->settle($promises);
    }

    /**
     * Создать заказ
     *
     * @param array $data Данные заказа
     *
     * @return PromiseInterface
     */
    public function create(array $data): PromiseInterface
    {
        v::not(v::key('id'))->assert($data);
        return Utils::unwrapSingleSettle($this->createOrUpdate([$data]))->then(
            static fn ($results) => $response->withBody(
                Utils::getJsonStream($response->getBody()->toArray()[0])
            )
        );
    }


    /**
     * Обновил заказ
     *
     * @param string $guid GUID Заказа
     * @param array  $data Обновленные данные
     *
     * @return PromiseInterface
     */
    public function update(string $guid, array $data): PromiseInterface
    {
        $data['id'] = $guid;
        return Utils::unwrapSingleSettle($this->createOrUpdate([$data]))->then(
            static fn ($response) => $response->withBody(
                Utils::getJsonStream($response->getBody()->toArray()[0])
            )
        );
    }

    /**
     * Распаковать результаты выполнения fulfilled
     * в массив response
     *
     * Из метода PromiseAggregator::settle.
     * Исключает из результатов ответы rejected.
     *
     * @param array $results Результаты выполнения
     *
     * @return array
     */
    private function _unwrapFulfilled(array $results): array
    {
        return array_map(
            static fn ($result) => $result['value'],
            array_filter(
                $results, static function ($result) {
                    return $result['state'] === PromiseInterface::FULFILLED;
                }
            )
        );
    }

    /**
     * Собрать ответы от методов, возвращающих
     * массив с rows в один массив
     *
     * @param array<ResponseInterface> $responses Ответы
     *
     * @return array
     */
    private function _collectRowsResponses(array $responses): array
    {
        return array_merge(...array_map(
            static fn ($response) =>  $response->getBody()->toArray()['rows'],
            $responses
        ));
    }

    /**
     * Преобразовать позиции с артикулами
     * в позиции с идентификаторами
     *
     * @param array $positions  Позиции
     * @param array $assortment Ассортимент
     *
     * @return array
     */
    private function _convertPositions(array $positions, array $assortment): array
    {
        $articles = array_column($assortment, 'article');
        $codes = array_column($assortment, 'code');
        $meta = array_column($assortment, 'meta');
        $ids = array_column($assortment, 'id');

        $result = [];
        foreach ($positions as $position) {
            $index = array_search($position['article'], $articles);
            $index = $index ? $index : array_search($position['article'], $codes);

            if ($index !== false) {
                unset($position['article']);
                $position['id'] = $ids[$index];
                $position['type'] = $meta[$index]['type'];
                $result[] = $position;
            }
        }

        return $result;
    }

    /**
     * Найти ассортимент по артикулам и создать позиции
     *
     * @param string $guid      GUID Заказа
     * @param array  $positions Позиции с артикулами
     *
     * @return PromiseInterface
     */
    private function _findPositions(
        string $guid,
        array $positions
    ): PromiseInterface {
        $articles = array_column($positions, 'article');
        return $this->assortment()->getByArticles($articles)->then(
            function ($results) use ($guid, $articles, $positions) {
                $assortment = $this->_collectRowsResponses(
                    $this->_unwrapFulfilled($results)
                );

                $assortmentArticles = array_column($assortment, 'article');
                $articles = array_filter(
                    $articles, static fn ($article) => !in_array(
                        $article, $assortmentArticles
                    )
                );

                if ($articles) {
                    return $this->assortment()->getByCodes($articles)->then(
                        function ($results) use ($guid, $positions, $assortment) {
                            return array_merge(
                                $assortment, $this->_collectRowsResponses(
                                    $this->_unwrapFulfilled($results)
                                )
                            );
                        }
                    );
                }

                return $assortment;
            }
        );
    }

    /**
     * Добавить позиции к заказу
     *
     * @param string $guid      GUID Заказа
     * @param array  $positions Позиции заказа
     *
     * @return PromiseInterface
     */
    public function addPositions(string $guid, array $positions): PromiseInterface
    {
        v::length(36, 36)->assert($guid);
        v::length(1)->each(
            v::allOf(
                v::anyOf(
                    v::key('article', v::stringType()),
                    v::allOf(
                        v::key('id', v::stringType()->length(36, 36)),
                        v::key('type', v::stringType()->in(['variant', 'product']))
                    )
                ),
                v::key('quantity', v::intType(), false),
                v::key('price', v::floatType(), false),
                v::key('vat', v::intType(), false),
                v::key('reserve', v::intType(), false),
                v::key('discount', v::intType(), false)
            )
        )->assert($positions);

        return Coroutine::of(function () use ($guid, $positions) {
            $result = [];

            $builder = $this->builder()->point("entity/customerorder/$guid/positions");
            $meta = $this->meta();

            $toAdd = [];
            $toFind = [];
            foreach ($positions as $index => $position) {
                if (isset($position['id'])) {
                    $position['assortment'] = [
                        'meta' => $meta->{$position['type']}($position['id'])
                    ];
                    unset($position['id'], $position['type']);
                    $toAdd[] = $position;
                    continue;
                }

                $toFind[] = $position;
            }

            foreach (array_chunk($toAdd, 500) as $chunk) {
                if ($chunk) {
                    $result[] = (
                        yield $this->pool()->add(
                            (clone $builder)->body($chunk)->build('POST')
                        )
                    );
                }
            }

            foreach (array_chunk($toFind, 500) as $chunk) {
                if ($chunk) {
                    $result[] = (yield $this->_findPositions($guid, $chunk)->then(
                        function ($assortment) use ($guid, $chunk) {
                            return $this->addPositions(
                                $guid, $this->_convertPositions($chunk, $assortment)
                            );
                        }
                    ));
                }
            }

            yield $result;
        });
    }

    /**
     * Добавить 1 позицию
     *
     * @param string $guid GUID Заказа
     * @param array  $data Данные позиции
     *
     * @return PromiseInterface
     */
    public function addPosition(string $guid, array $data): PromiseInterface
    {
        return $this->addPositions($guid, [$data])->then(
            static fn ($responses) => $responses[0]->withBody(
                Utils::getJsonStream($responses[0]->getBody()->toArray()[0])
            )
        );
    }
}
