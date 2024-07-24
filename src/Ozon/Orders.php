<?php
/**
 * Класс для работы с заказами Ozon
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Ozon;

use CashCarryShop\Sizya\OrdersGetterInterface;
use GuzzleHttp\Promise\Utils;
use Respect\Validation\Validator as v;
use DateTimeZone;

/**
 * Класс для работы с заказами Ozon
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class Orders extends AbstractSource implements OrdersGetterInterface
{
    /**
     * Создать экземпляр элемента синхронизации
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        $timezone = new DateTimeZone('UTC');
        $format = 'Y-m-d\TH:i:s\Z';

        $defaults = [
            'limit' => 100,
            'dir' => 'ASC',
            'cutoff_from' => date_create('-30 days', $timezone)->format($format),
            'cutoff_to' => date_create('now', $timezone)->format($format),
            'since' => date_create('-30 days', $timezone)->format($format),
            'to' => date_create('now', $timezone)->format($format),
            'analytics_data' => true,
            'barcodes' => true,
            'financial_data' => true,
            'translit' => true,
            'status' => null,
            'unfulfilled' => true
        ];

        parent::__construct(array_replace($defaults, $settings));

        v::allOf(
            v::key('limit', v::intType()),
            v::key('dir', v::in(['ASC', 'DESC'])),
            v::key('cutoff_from', v::dateTime($format)),
            v::key('cutoff_to', v::dateTime($format)),
            v::key('since', v::dateTime($format)),
            v::key('to', v::dateTime($format)),
            v::key('analytics_data', v::boolType()),
            v::key('barcodes', v::boolType()),
            v::key('financial_data', v::boolType()),
            v::key('translit', v::boolType()),
            v::key('status', v::optional(v::in([
                'acceptance_in_progress',
                'awaiting_approve',
                'awaiting_packaging',
                'awaiting_registration',
                'awaiting_deliver',
                'arbitration',
                'client_arbitration',
                'delivering',
                'driver_pickup',
                'not_accepted'
            ]))),
            v::key('provider_id', v::each(v::intType()), false),
            v::key('delivery_method_id', v::each(v::intType()), false),
            v::key('warehouse_id', v::each(v::intType()), false),
            // При установке этого параметра в true, из метода `get`
            // будет возвращены только не обработанные заказы
            v::key('unfulfilled', v::boolType()),
        )->assert($this->settings);
    }

    /**
     * Конвертировать позицию
     *
     * @param array $position Позиция
     *
     * @return array
     */
    private function _convertPosition(array $position): array
    {
        return [
            'id' => (string) $position['sku'],
            'orderId' => (string) $position['sku'],
            'article' => $position['offer_id'],
            'quantity' => $position['quantity'],
            'reserve' => $position['quantity'],
            'currency' => $position['currency_code'],
            'price' => (float) $position['price'],
            'discount' => 0.0,
            'original' => $position
        ];
    }

    /**
     * Конвертировать заказ
     *
     * @param array $order Заказ
     *
     * @return array
     */
    private function _convertOrder(array $order): array
    {
        return [
            'id' => $order['posting_number'],
            'created' => $order['in_process_at'],
            'status' => $order['substatus'],
            'shipment_date' => $order['shipment_date'],
            'delivering_date' => $order['delivering_date'],
            'positions' => array_map(
                fn ($position) => $this->_convertPosition($position),
                $order['products']
            ),
            'original' => $order
        ];
    }

    /**
     * Получить необработанные заказы по запросу к api
     *
     * @return array
     */
    private function _getUnfulfilledOrders(): array
    {
        $offset = 0;
        $loop = true;

        $orders = [];
        $maxOffset = $counter = $this->getSettings('limit');

        while ($loop && $offset < $maxOffset) {
            $result = $this->decode(
                $this->send(
                    $this->builder()->point('v3/posting/fbs/unfulfilled/list')
                        ->body([
                            'dir' => $this->getSettings('dir'),
                            'offset' => $offset,
                            'limit' => $counter > 1000 ? 1000 : $counter,
                            'filter' => [
                                'cutoff_from' => $this->getSettings('cutoff_from'),
                                'cutoff_to' => $this->getSettings('cutoff_to'),
                                'provider_id' => $this->getSettings('provider_id', []),
                                'status' => $this->getSettings('status'),
                                'delivery_method_id' => $this->getSettings(
                                    'delivery_method_id', []
                                ),
                                'warehouse_id' => $this->getSettings(
                                    'warehouse_id', []
                                )
                            ],
                            'with' => [
                                'barcodes' => $this->getSettings('barcodes'),
                                'translit' => $this->getSettings('translit'),
                                'analytics_data' => $this->getSettings(
                                    'analytics_data'
                                ),
                                'financial_data' => $this->getSettings(
                                    'financial_data'
                                )
                            ]
                        ])
                        ->build('POST')
                )
            )->wait();

            if ($loop = $result['result']['count'] === 1000) {
                $offset += 1000;
            }

            $orders = array_merge(
                $orders, array_map(
                    fn ($order) => $this->_convertOrder($order),
                    $result['result']['postings']
                )
            );
        }

        return $orders;
    }

    /**
     * Получить заказы по запросу к api
     *
     * @return array
     */
    private function _getFulfilledOrders(): array
    {
        $offset = 0;
        $loop = true;

        $orders = [];
        $maxOffset = $counter = $this->getSettings('limit');

        while ($loop && $offset < $maxOffset) {
            $result = $this->decode(
                $this->send(
                    $this->builder()->point('v3/posting/fbs/list')
                        ->body([
                            'dir' => $this->getSettings('dir'),
                            'offset' => $offset,
                            'limit' => $counter > 1000 ? 1000 : $counter,
                            'filter' => [
                                'since' => $this->getSettings('since'),
                                'to' => $this->getSettings('to'),
                                'provider_id' => $this->getSettings('provider_id', []),
                                'status' => $this->getSettings('status'),
                                'delivery_method_id' => $this->getSettings(
                                    'delivery_method_id', []
                                ),
                                'warehouse_id' => $this->getSettings(
                                    'warehouse_id', []
                                )
                            ],
                            'with' => [
                                'barcodes' => $this->getSettings('barcodes'),
                                'translit' => $this->getSettings('translit'),
                                'financial_data' => $this->getSettings(
                                    'financial_data'
                                ),
                                'analytics_data' => $this->getSettings(
                                    'analytics_data'
                                ),
                            ]
                        ])
                        ->build('POST')
                )
            )->wait();

            if ($loop = $result['result']['has_next']) {
                $offset += 1000;
            }

            $orders = array_merge(
                $orders, array_map(
                    fn ($order) => $this->_convertOrder($order),
                    $result['result']['postings']
                )
            );
        }

        return $orders;
    }

    /**
     * Получить заказы
     *
     * Смотреть `OrdersGetterInterface::getOrders`
     *
     * @return array
     */
    public function getOrders(): array
    {
        return $this->getSettings('unfulfilled')
            ? $this->_getUnfulfilledOrders()
            : $this->_getFulfilledOrders();
    }

    /**
     * Получить заказы по идентификаторам
     *
     * Смотреть `OrdersGetterInterface::getOrdersByIds`
     *
     * @param array $orderIds Идентификаторы заказов
     *
     * @return array
     */
    public function getOrdersByIds(array $orderIds): array
    {
        $promises = [];
        foreach ($orderIds as $orderId) {
            $promises[] = $this->decode(
                $this->send(
                    $this->builder()->point('v3/posting/fbs/get')
                        ->body([
                            'posting_number' => $orderId,
                            'with' => [
                                'barcodes' => $this->getSettings('barcodes'),
                                'translit' => $this->getSettings('translit'),
                                'financial_data' => $this->getSettings(
                                    'financial_data'
                                ),
                                'analytics_data' => $this->getSettings(
                                    'analytics_data'
                                ),
                            ]
                        ])->build('POST')
                )
            );
        }

        return array_map(
            fn ($order) => $this->_convertOrder($order['result']),
            Utils::all($promises)->wait()
        );
    }

    /**
     * Получить заказ по идентификатору
     *
     * Смотреть `OrdersGetterInterface::getOrderById`
     *
     * @param string $orderId Идентификатор заказа
     *
     * @return array
     */
    public function getOrderById(string $orderId): array
    {
        return $this->getOrdersByIds([$orderId])[0];
    }
}
