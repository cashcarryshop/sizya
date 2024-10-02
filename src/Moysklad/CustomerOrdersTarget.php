<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
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

use CashCarryShop\Sizya\OrdersCreatorInterface;
use CashCarryShop\Sizya\OrdersUpdaterInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Sizya\DTO\{OrderDTO, OrderCreateDTO, OrderUpdateDTO};
use CashCarryShop\Sizya\DTO\{PositionDTO, PositionCreateDTO, PositionUpdateDTO};
use CashCarryShop\Sizya\DTO\{AdditionalDTO, AdditionalCreateDTO, AdditionalUpdateDTO};
use CashCarryShop\Sizya\DTO\ByErrorDTO;

use CashCarryShop\Sizya\Utils as SizyaUtils;
use GuzzleHttp\Promise\Utils as PromiseUtils;

/**
 * Класс для работы с заказами покупателей МойСклад
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class CustomerOrdersTarget extends CustomerOrdersSource
    implements SynchronizerTargetInterface,
               OrdersCreatorInterface,
               OrdersUpdaterInterface
{
    /**
     * Создать заказ
     *
     * @param OrderCreateDTO $order Заказ
     *
     * @see OrdersCreatorInterface
     *
     * @return OrderDTO|ByErrorDTO
     */
    public function createOrder(OrderCreateDTO $order): OrderDTO|ByErrorDTO
    {
        return $this->_createOrUpdate([$order])[0];
    }

    /**
     * Создать переданные заказы
     *
     * @param OrderCreateDTO[] $orders Заказы
     *
     * @see OrdersCreatorInterface
     *
     * @return array<OrderDTO|ByErrorDTO>
     */
    public function massCreateOrders(array $orders): array
    {
        return $this->_createOrUpdate($orders);
    }

    /**
     * Обновить заказ
     *
     * @param OrderUpdateDTO $order Заказ
     *
     * @see OrdersUpdaterInterface
     *
     * @return OrderDTO|ByErrorDTO
     */
    public function updateOrder(OrderUpdateDTO $order): OrderDTO|ByErrorDTO
    {
        return $this->_createOrUpdate([$order])[0];
    }

    /**
     * Обновить заказы
     *
     * @param OrderUpdateDTO[] $orders Заказы
     *
     * @see OrdersUpdaterInterface
     *
     * @return array<OrderDTO|ByErrorDTO>
     */
    public function massUpdateOrders(array $orders): array
    {
        return $this->_createOrUpdate($orders);
    }

    /**
     * Создать или обновить заказы
     *
     * @param array<OrderCreateDTO|OrderUpdateDTO> $orders Заказы
     *
     * @return array<OrderDTO|ErrorDTO>
     */
    private function _createOrUpdate(array $orders): array
    {
        [
            $validated,
            $errors
        ] = SizyaUtils::splitByValidationErrors(
            $this->getValidator(), $productsIds, [
                new Assert\NotBlank,
                new Assert\Type([OrderCreateDTO::class, OrderUpdateDTO::class]),
                new Assert\Valid
            ]
        );
        unset($orders);

        $this->_prepareOrders($validated, $errors);

        $builder = $this->builder()
            ->point('entity/customerorder')
            ->expand('positions.assortment');

        [
            'chunks'   => $chunks,
            'promises' => $promises
        ] = SizyaUtils::getByChunks(
            $validated,
            fn ($order) => $this->_convertOrder($order),
            static fn ($data) => (clone $builder)->body($data)->build('POST')
        );

        return \array_merge(
            SizyaUtils::mapResults(
                $chunks,
                PromiseUtils::settle($promises)->wait(),
                static fn ($response, $chunk) => [
                    'values' => $chunk,
                    'dtos'   => \array_map(
                        static fn ($item) => OrderDTO::fromArray([
                            'id'           => $item['id'],
                            'article'      => $item['name'],
                            'created'      => Utils::dateToUtc($item['created']),
                            'status'       => Utils::guidFromMeta($item['state']['meta']),
                            'shipmentDate' => Utils::dateToUtc($item['deliveryPlannedMoment']),
                            'description'  => $item['description'],
                            'additionals'  => \array_map(
                                static fn ($additional) => AdditionalDTO::fromArray([
                                    'id'       => $additional['id'],
                                    'entityId' => $additional['id'],
                                    'name'     => $additional['name'],
                                    'value'    => $additional['value'],
                                    'original' => $additional
                                ]),
                                $item['attributes']
                            ),
                            'positions' => \array_map(
                                static fn ($position) => PositionDTO::fromArray([
                                    'id'        => $position['id'],
                                    'productId' => $position['assortment']['id'],
                                    'type'      => $position['assortment']['meta']['type'],
                                    'quantity'  => $position['quantity'],
                                    'reserve'   => $position['reserve'] ?? 0,
                                    'price'     => (float) ($position['price'] / 100),
                                    'discount'  => (float) $position['discount'],
                                    'original'  => $position
                                ]),
                                $item['positions']['rows']
                            ),
                            'original' => $item
                        ]),
                        $this->decodeResponse($response)
                    )
                ]
            )
        );
    }

    /**
     * Обработать данные создания/обновления заказов.
     *
     * @param array $validated Ссылка на валидированные данные
     * @param array $errors    Ссылка на ошибки валидации данных
     *
     * @return void
     */
    private function _prepareOrders(array &$validated, array &$errors): void
    {
        // Собираем артикулы товаров для их получения
        $articles = [];
        foreach ($validated as $oIdx => $order) {
            foreach ($order->positions as $pIdx => $position) {
                if ($position->productId) {
                    continue;
                }

                $data = [
                    'oIdx'    => $oIdx,
                    'pIdx'    => $pIdx,
                    'article' => $position->article
                ];

                if (isset($articles[$position->article])) {
                    $articles[$position->article][] = $data;
                    continue;
                }

                $articles[$position->article] = [$data];
            }
        }

        $products = $this->getSettings('products')
            ->getProductsByArticles(\array_keys($articles));

        foreach ($products as $item) {
            if ($item instanceof ByErrorDTO) {
                $errors[] = ByErrorDTO::fromArray([
                    'type'   => ByErrorDTO::NOT_FOUND,
                    'value'  => $validated[$articles[$item->value]['oIdx']],
                    'reason' => $item
                ]);
                unset($validated[$articles[$item->value]['oIdx']]);
                continue;
            }

            $data = $articles[$item->article];
            $validated[$data['oIdx']]->positions[$data['pIdx']]->offerId = $item->id;
        }
    }

    /**
     * Конвертировать заказ для создания
     *
     * @param OrderCreateDTO|OrderUpdateDTO $order Заказ
     *
     * @return array
     */
    private function _convertOrder(OrderCreateDTO|OrderUpdateDTO $order): array
    {
        $data = [
            'organization' => $this->getSettings('organization'),
            'agent'        => $this->getSettings('agent')
        ];

        $this->_setIfExistInSettings('vatEnabled', $data);
        $this->_setIfExistInSettings('vatIncluded', $data);
        $this->_setIfExistInSettings('project', $data)
            && $data['project'] = $this->meta()->project($data['project']);
        $this->_setIfExistInSettings('contract', $data)
            && $data['contract'] = $this->meta()->contract($data['contract']);
        $this->_setIfExistInSettings('store', $data)
            && $data['store'] = $this->meta()->store($data['store']);
        $this->_setIfExistInSettings('salesChannel', $data)
            && $data['salesChannel'] = $this->meta()->salesChannel($data['salesChannel']);

        SizyaUtils::setIfNotNull('id',          $order, $data);
        SizyaUtils::setIfNotNull('article',     $order, $data, 'name');
        SizyaUtils::setIfNotNull('description', $order, $data);
        SizyaUtils::setIfNotNull('created',     $order, $data)
            && $data['created'] = Utils::dateToMoysklad($data['created']);

        SizyaUtils::setIfNotNull('status', $order, $data, 'state')
            && $data['state'] = [
                'meta' => $this->meta()->create(
                    "entity/customerorder/metadata/states/{$data['state']}",
                    'state'
                )
            ];

        SizyaUtils::setIfNotNull('shipment_date', $order, $data, 'deliveryPlannedMoment')
            && $data['deliveryPlannedMoment'] = Utils::dateToMoysklad(
                $data['deliveryPlannedMoment']
            );

        SizyaUtils::setIfNotNull('additional', $order, $data, 'attributes')
            && $data['attributes'] = \array_map(
                fn ($additional) => $this->_convertAdditional($additional),
                $data['attributes']
            );

        SizyaUtils::setIfNotNull('positions', $order, $data)
            && $data['positions'] = \array_map(
                fn ($position) => $this->_convertPosition($position),
                $data['positions']
            );

        return $data;
    }

    /**
     * Установить значение если оно имеется в настройках
     *
     * @param string $key  Ключ
     * @param array  $data Куда установить значение
     *
     * @return bool Было ли значение установлено
     */
    private function _setIfExistInSettings(string $key, array &$data): bool
    {
        if ($value = $this->getSettings($key)) {
            $data[$key] = $value;
            return true;
        }

        return false;
    }

    /**
     * Конвертировать доп. поле для создания/обновления
     *
     * @param AdditionalCreateDTO|AdditionalUpdateDTO $additional Доп поле
     *
     * @return array
     */
    private function _convertAdditional(AdditionalCreateDTO|AdditionalUpdateDTO $additional): array
    {
        return [
            'name'  => $additional['name'],
            'value' => $additional['value'],
            'meta'  => $this->meta()->create(
                "entity/customerorder/metadata/attributes/{$additional->entityId}",
                'attributemetadata'
            )
        ];
    }

    /**
     * Конвертировать позицию для создания/обновления
     *
     * @param PositionCreateDTO|PositionUpdateDTO $position Позиция
     *
     * @return array
     */
    private function _convertPosition(PositionCreateDTO|PositionUpdateDTO $position): array
    {
        $data = [];

        SizyaUtils::setIfNotNull('id',       $position, $data);
        SizyaUtils::setIfNotNull('quantity', $position, $data);
        SizyaUtils::setIfNotNull('discount', $position, $data);
        SizyaUtils::setIfNotNull('price',    $position, $data)
            && $data['price'] = $data['price'] * 100;

        if ($position->productId) {
            $type = 'product';
            if ($position->type) {
                $type = $position->type;
            }

            $data['assortment'] = [
                'meta' => $this->meta()->$type($position->productId)
            ];

            return $data;
        }

        $data['assortment'] = null;

        return $data;
    }
}
