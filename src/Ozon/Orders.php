<?php
/**
 * Класс остатков
 *
 * PHP version 8
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Ozon;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\Coroutine;
use Respect\Validation\Validator as v;
use CashCarryShop\Sizya\Http\Utils;
use DateTimeZone;

/**
 * Класс с настройками и логикой
 * получения заказов Ozon
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
final class Orders extends AbstractEntity
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

            // При установке этого параметра в True
            // в общий список заказов будут включены
            // "не обработанные" заказы.
            // По-умаолчанию: True
            v::key('unfulfilled', v::boolType()),
        )->assert($this->settings);
    }

    /**
     * Получить необработанные заказы по запросу к api
     *
     * @return PromiseInterface
     */
    private function _getUnfulfilledOrdersByApi(): PromiseInterface
    {
        return Coroutine::of(function () {
            $offset = 0;
            $loop = true;
            $result = [];

            while ($loop) {
                $promise = $this->getPool('orders')->add(
                    $this->builder()->point('v3/posting/fbs/unfulfilled/list')
                        ->body([
                            'dir' => $this->getSettings('dir'),
                            'offset' => $offset,
                            'limit' => 1000,
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
                );

                $promise->then(
                    static function ($response) use (&$offset, &$loop) {
                        $count = $response->getBody()->toArray()['result']['count'];
                        $response->getBody()->rewind();

                        if ($loop = $count === 1000) {
                            $offset += 1000;
                        }
                    },
                    static function () use (&$loop) {
                        $loop = false;
                    }
                );

                $result[] = (yield $promise);
            }

            yield $result;
        });
    }

    /**
     * Получить заказы по запросу к api
     *
     * @return PromiseInterface
     */
    private function _getFulfilledOrdersByApi(): PromiseInterface
    {
        return Coroutine::of(function () {
            $offset = 0;
            $loop = true;
            $result = [];

            while ($loop) {
                $promise = $this->getPool('orders')->add(
                    $this->builder()->point('v3/posting/fbs/list')
                        ->body([
                            'dir' => $this->getSettings('dir'),
                            'offset' => $offset,
                            'limit' => 1000,
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
                );

                $promise->then(
                    static function ($response) use (&$offset, &$loop) {
                        $loop = $response->getBody()->toArray()['result']['has_next'];
                        $response->getBody()->rewind();
                        if ($loop) {
                            $offset += 1000;
                        }
                    },
                    static function () use (&$loop) {
                        $loop = false;
                    }
                );

                $result[] = (yield $promise);
            }

            return $result;
        });
    }

    /**
     * Получить заказы
     *
     * @return PromiseInterface
     */
    public function getOrders(): PromiseInterface
    {
        $promises = [$this->_getFulfilledOrdersByApi()];

        if ($this->getSettings('unfulfilled')) {
            $promises[] = $this->_getUnfulfilledOrdersByApi();
        }

        return $this->getPromiseAggregator()->settle($promises)->then(
            function ($results) {
                $output = [];
                $postings = [];

                foreach ($results as $result) {
                    if ($result['state'] === PromiseInterface::REJECTED) {
                        $output[] = $result;
                        continue;
                    }

                    $response = $result['value'];
                    $items = $response->getBody()->toArray()['result']['postings'];
                    $postings[] = $items;
                }

                $postings = array_merge(...$postings);
                $postingNumbers = array_unique(
                    array_column($postings, 'posting_number')
                );

                $postings = array_intersect_key($postings, $postingNumbers);

                return $postings;
            }
        );
    }

    /**
     * Получить заказ по идентификатору
     *
     * @param string $orderId Идентификатор заказа
     *
     * @return PromiseInterface<array>
     */
    public function getById(string $orderId): PromiseInterface
    {
        return $this->getPool('orders')->add(
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
        );
    }
}
