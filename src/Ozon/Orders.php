<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Ozon;

use CashCarryShop\Sizya\OrdersGetterInterface;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\PositionDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\Utils as SizyaUtils;
use Symfony\Component\Validator\Constraints as Assert;
use GuzzleHttp\Promise\Utils as PromiseUtils;
use Psr\Http\Message\RequestInterface;
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
            'limit'              => 100,
            'dir'                => 'ASC',
            'cutoff_from'        => date_create('-30 days', $timezone)->format($format),
            'cutoff_to'          => date_create('now',      $timezone)->format($format),
            'since'              => date_create('-30 days', $timezone)->format($format),
            'to'                 => date_create('now',      $timezone)->format($format),
            'analytics_data'     => true,
            'barcodes'           => true,
            'financial_data'     => true,
            'translit'           => true,
            'status'             => null,
            'provider_id'        => [],
            'delivery_method_id' => [],
            'warehouse_id'       => [],
            'unfulfilled'        => true
        ];

        parent::__construct(array_replace($defaults, $settings));
    }

    /**
     * Правила валидации для настроек
     *
     * @return array
     */
    protected function rules(): array
    {
        $format = 'Y-m-d\TH:i:s\Z';

        return array_merge(
            parent::rules(), [
                'limit' => [
                    new Assert\Type('int'),
                    new Assert\Range(min: 100),
                ],
                'dir' => [
                    new Assert\Type('string'),
                    new Assert\Choice(['ASC', 'DESC'])
                ],
                'cutoff_from' => [
                    new Assert\Type('string'),
                    new Assert\DateTime($format)
                ],
                'cutoff_to' => [
                    new Assert\Type('string'),
                    new Assert\DateTime($format)
                ],
                'since' => [
                    new Assert\Type('string'),
                    new Assert\DateTime($format)
                ],
                'to' => [
                    new Assert\Type('string'),
                    new Assert\DateTime($format)
                ],
                'analytics_data' => [
                    new Assert\Type('bool'),
                ],
                'barcodes' => [
                    new Assert\Type('bool'),
                ],
                'financial_data' => [
                    new Assert\Type('bool'),
                ],
                'translit' => [
                    new Assert\Type('bool'),
                ],
                'status' => [
                    new Assert\Type(['null', 'string']),
                    new Assert\When(
                        expression: 'value !== null',
                        constraints: [
                            new Assert\Choice([
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
                            ])
                        ]
                    )
                ],
                'provider_id' => [
                    new Assert\Type('array'),
                    new Assert\All(new Assert\Type('int')),
                ],
                'delivery_method_id' => [
                    new Assert\Type('array'),
                    new Assert\All(new Assert\Type('int')),
                ],
                'warehouse_id' => [
                    new Assert\Type('array'),
                    new Assert\All(new Assert\Type('int')),
                ],
                'unfulfilled' => [
                    new Assert\Type('bool'),
                ]
            ]
        );
    }

    /**
     * Получить заказы
     *
     * @see OrdersGetterInterface
     *
     * @return OrderDTO[]
     */
    public function getOrders(): array
    {
        return $this->getSettings('unfulfilled')
            ? $this->_getUnfulfilledOrders()
            : $this->_getFulfilledOrders();
    }

    /**
     * Получить заказ по идентификатору
     *
     * @param string $orderId Идентификатор заказа
     *
     * @see OrdersGetterInterface
     *
     * @return OrderDTO|ByErrorDTO
     */
    public function getOrderById(string $orderId): OrderDTO|ByErrorDTO
    {
        return $this->getOrdersByIds([$orderId])[0];
    }

    /**
     * Получить заказы по идентификаторам
     *
     * @param array $ordersIds Идентификаторы заказов
     *
     * @see OrdersGetterInterface
     *
     * @return array<int, OrderDTO|ByErrorDTO>
     */
    public function getOrdersByIds(array $ordersIds): array
    {
        $builder = $this->builder()->point('v3/posting/fbs/get');

        $chunks   = [];
        $promises = [];

        foreach ($ordersIds as $orderId) {
            $chunks[]   = [$orderId];
            $promises[] = $this->send(
                (clone $builder)->body([
                    'posting_number' => $orderId,
                    'with'           => [
                        'barcodes'       => $this->getSettings('barcodes'),
                        'translit'       => $this->getSettings('translit'),
                        'financial_data' => $this->getSettings('financial_data'),
                        'analytics_data' => $this->getSettings('analytics_data'),
                    ]
                ])->build('POST')
            );
        }

        $orders = \array_map(
            static function ($item) {
                if ($item instanceof ByErrorDTO
                    && $item->type === ByErrorDTO::HTTP
                    && $item->reason->getResponse()->getStatusCode() === 404
                ) {
                    $item->type = ByErrorDTO::NOT_FOUND;
                }

                return $item;
            },
            SizyaUtils::mapResults(
                $chunks,
                PromiseUtils::settle($promises)->wait(),
                function ($response) {
                    $order = $this->_convertOrder($this->decodeResponse($response)['result']);

                    return [
                        'dtos'   => [$order],
                        'values' => [$order->id]
                    ];
                }
            )
        );

        $errors  = [];
        $correct = [];

        foreach ($orders as $item) {
            if ($item instanceof ByErrorDTO) {
                $errors[] = $item;
                continue;
            }

            $correct[] = $item;
        }

        return \array_merge($this->_fixProductsIds($correct), $errors);
    }

    /**
     * Получить необработанные заказы по запросу к api
     *
     * @return OrderDTO[]
     */
    private function _getUnfulfilledOrders(): array
    {
        $limit = $this->getSettings('limit');

        return $this->_getAll(
            $this->builder()->point('v3/posting/fbs/unfulfilled/list'),
            fn ($builder, $offset) => $builder->body([
                'dir'    => $this->getSettings('dir'),
                'offset' => $offset,
                'limit'  => $limit > 1000 ? 1000 : $limit,
                'filter' => [
                    'cutoff_from'        => $this->getSettings('cutoff_from'),
                    'cutoff_to'          => $this->getSettings('cutoff_to'),
                    'provider_id'        => $this->getSettings('provider_id', []),
                    'status'             => $this->getSettings('status'),
                    'delivery_method_id' => $this->getSettings('delivery_method_id', []),
                    'warehouse_id'       => $this->getSettings('warehouse_id', [])
                ],
                'with' => [
                    'barcodes'       => $this->getSettings('barcodes'),
                    'translit'       => $this->getSettings('translit'),
                    'analytics_data' => $this->getSettings('analytics_data'),
                    'financial_data' => $this->getSettings('financial_data')
                ]
            ])->build('POST')
        );
    }

    /**
     * Получить заказы по запросу к api
     *
     * @return OrderDTO[]
     */
    private function _getFulfilledOrders(): array
    {
        $limit = $this->getSettings('limit');

        return $this->_getAll(
            $this->builder()->point('v3/posting/fbs/list'),
            fn ($builder, $offset) => $builder->body([
                'dir'    => $this->getSettings('dir'),
                'offset' => $offset,
                'limit'  => $limit > 1000 ? 1000 : $limit,
                'filter' => [
                    'since'              => $this->getSettings('since'),
                    'to'                 => $this->getSettings('to'),
                    'provider_id'        => $this->getSettings('provider_id', []),
                    'status'             => $this->getSettings('status'),
                    'delivery_method_id' => $this->getSettings('delivery_method_id', []),
                    'warehouse_id'       => $this->getSettings('warehouse_id', [])
                ],
                'with' => [
                    'barcodes'       => $this->getSettings('barcodes'),
                    'translit'       => $this->getSettings('translit'),
                    'financial_data' => $this->getSettings('financial_data'),
                    'analytics_data' => $this->getSettings('analytics_data'),
                ]
            ])->build('POST')
        );
    }

    /**
     * Вспомогательный метод с основной логикой получения заказов
     *
     * @param RequestBuilder                             $builder      Сборчик заказов
     * @param callable(RequestBuilder): RequestInterface $buildRequest Собрать запрос
     *
     * @return OrderDTO[]
     */
    private function _getAll(
        RequestBuilder $builder,
        callable       $buildRequest
    ): array {
        $offset = 0;
        $loop = true;

        $orders = [];
        $maxOffset = $this->getSettings('limit');

        $skus          = [];
        $skusPositions = [];

        while ($loop && $offset < $maxOffset) {
            $postings = $this->decode(
                $this->send(
                    $buildRequest(clone $builder, $offset)
                )
            )->wait()['result']['postings'];

            if ($loop = \count($postings) === 1000) {
                $offset += 1000;
            }

            foreach ($postings as $posting) {
                $orders[] = $order = $this->_convertOrder($posting);
                foreach ($order->positions as $index => $position) {
                    $skus[]          = $position->productId;
                    $skusPositions[] = $index;
                }
            }
        }

        return $this->_fixProductsIds($orders, $skus, $skusPositions);
    }

    /**
     * Конвертировать заказ
     *
     * @param array $order Заказ
     *
     * @return OrderDTO
     */
    private function _convertOrder(array $order): OrderDTO
    {
        return OrderDTO::fromArray([
            'id'             => $order['posting_number'],
            'created'        => $order['in_process_at'],
            'status'         => $order['substatus'],
            'shipmentDate'   => $order['shipment_date'],
            'deliveringDate' => $order['delivering_date'],
            'additionals'    => [],
            'positions'      => \array_map(
                fn ($position) => $this->_convertPosition($position),
                $order['products']
            ),
            'externalcode'   => \sha1($order['posting_number']),
            'original' => $order
        ]);
    }

    /**
     * Конвертировать позицию
     *
     * @param array $position Позиция
     *
     * @return PositionDTO
     */
    private function _convertPosition(array $position): PositionDTO
    {
        return PositionDTO::fromArray([
            'id'        => (string) $position['sku'],
            'productId' => (string) $position['sku'],
            'article'   => $position['offer_id'],
            'quantity'  => $position['quantity'],
            'reserve'   => $position['quantity'],
            'currency'  => $position['currency_code'],
            'price'     => (float) $position['price'],
            'discount'  => 0.0,
            'original'  => $position
        ]);
    }

    /**
     * Исправить идентификаторы твоаров в позициях.
     *
     * @param array  $orders        Полученные заказы
     * @param ?array $skus          SKU товаров
     * @param ?array $skuspositions индексы позиций для $orders->positions[$position]
     *
     * @return array
     */
    private function _fixProductsIds(
        array  $orders,
        ?array $skus          = null,
        ?array $skusPositions = null
    ): array {
        if ($skus === null) {
            $skus          = [];
            $skusPositions = [];

            foreach ($orders as $idx => $order) {
                foreach ($order->positions as $index => $position) {
                    $skus[]          = $position->productId;
                    $skusPositions[] = $index;
                }
            }
        }

        $builder = $this->builder()->point('v2/product/info/list');

        $chunks   = \array_chunk(\array_unique($skus), 1000);
        $promises = [];

        foreach ($chunks as $chunk) {
            $promises[] = $this->send(
                (clone $builder)
                    ->body(['sku' => $chunk])
                    ->build('POST')
            );
        }

        $datas = SizyaUtils::mapResults(
            $chunks,
            PromiseUtils::settle($promises)->wait(),
            function ($response, $chunk) {
                $datas  = [];
                $values = [];

                foreach ($this->decodeResponse($response)['result']['items'] as $product) {
                    $datas[] = [
                        'productId' => $product['id'],
                        'sku'       => $values[] = (string) $product['sku']
                    ];
                }

                return [
                    'dtos'   => $datas,
                    'values' => $values
                ];
            }
        );
        unset($chunks);
        unset($promises);
        unset($builder);


        $productSkus = \array_map(
            static function ($data) {
                if ($data instanceof ByErrorDTO) {
                    return $data->value;
                }

                return $data['sku'];
            },
            $datas
        );

        \asort($skus,        SORT_STRING);
        \asort($productSkus, SORT_STRING);

        foreach ($productSkus as $idx => $sku) {
            $error = $datas[$idx] instanceof ByErrorDTO;

            if (\current($skus) === $sku) {
                do {
                    $index = \key($skus);
                    if ($error) {
                        unset($orders[$index]);
                        \next($skus);
                        continue;
                    }

                    $orders[$index]
                    ->positions[$skusPositions[$index]]
                    ->productId = (string) $datas[$idx]['productId'];
                    \next($skus);
                } while (\current($skus) === $sku);
            }
        }

        return \array_values($orders);
    }
}
