<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Tests\Traits;

use CashCarryShop\Sizya\Moysklad\Enums\FilterSign;
use CashCarryShop\Sizya\Moysklad\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Трейт с методами для работы с МойСклад классами.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see OrdersGetterByAdditionalInterface
 */
trait InteractsWithMoysklad
{
    use InteractsWithHttp;

    /**
     * Создать response для метода.
     *
     * @param string $method  Метод
     * @param array  $options Опции
     *
     * @return callable(RequestInterface): ResponseInterface
     */
    protected static function createMethodResponse(string $method, array $options = []): callable
    {
        static $methods;

        $methods ??= [
            'get@1.2/report/stock/bystore/current' => function ($request, $options) {
                return static::createJsonResponse(
                    body: \array_map(
                        static fn ($stock) => [
                            'assortmentId' => $stock->id,
                            'storeId'      => $stock->warehouseId,
                            'quantity'     => $stock->quantity
                        ],
                        $options['expected']
                    )
                );
            },
            'get@1.2/entity/customerorder' => function ($request, $options) {
                $body = static::getResponseData(
                    'api.moysklad.ru/api/remap/1.2/entity/customerorder'
                )['body'];

                $item = $body['rows'][0];

                $body['rows'] = \array_map(
                    function ($order) use ($item) {
                        $replaces = [
                            '$id'      => $order->id,
                            '$orderId' => $order->id,
                            '$stateId' => $order->status
                        ];

                        $item['id']           = $order->id;
                        $item['externalCode'] = $order->externalCode;
                        $item['created']      = Utils::dateToMoysklad($order->created);
                        $item['description']  = $order->description;

                        $item['deliveryPlannedMoment'] = $order->shipmentDate
                            ? Utils::dateToMoysklad($order->shipmentDate)
                            : null;

                        $item['state']['meta']['href'] = \strtr(
                            $item['state']['meta']['href'],
                            $replaces
                        );

                        $item['meta']['href'] = \strtr(
                            $item['meta']['href'],
                            $replaces
                        );

                        $item['positions']['meta']['href'] = \strtr(
                            $item['positions']['meta']['href'],
                            $replaces
                        );

                        $item['attributes'] = \array_map(
                            fn ($additional) => static::makeAttribute([
                                'id'       => $additional->id,
                                'name'     => $additional->name,
                                'type'     => \gettype($additional->value),
                                'value'    => $additional->value,
                                'category' => 'customerorder'
                            ]),
                            $order->additionals
                        );

                        $item['positions']['rows'] = \array_map(
                            function ($position) use ($order) {
                                $msPosition = static::makePosition([
                                    'id'       => $position->id,
                                    'entityId' => $order->id,
                                    'quantity' => $position->quantity,
                                    'reserve'  => $position->reserve,
                                    'price'    => $position->price * 100,
                                    'discount' => $position->discount,
                                    'vat'      => $position->vat,
                                    'category' => 'customerorder'
                                ]);

                                $msPosition['assortment'] =
                                    static::makeAssortmentItem([
                                        'id'      => $position->productId,
                                        'article' => $position->article,
                                        'type'    => $position->type
                                    ]);

                                return $msPosition;
                            },
                            $order->positions
                        );

                        return $item;
                    },
                    $options['expected']
                );

                $body['meta']['size'] = \count($body['rows']);
                $body['meta']['href'] = (string) $request->getUri();

                return static::createJsonResponse(body: $body);
            },
            'get@1.2/entity/assortment' => function ($request, $options) {
                $body = static::getResponseData(
                    'api.moysklad.ru/api/remap/1.2/entity/assortment'
                )['body'];
                $item = $body['rows'][0];

                $body['rows'] = \array_map(
                    function ($expected) use ($item) {
                        $type = \random_int(0, 3) === 3 ? 'product' : 'variant';

                        $item['id']           = $expected->id;
                        $item['meta']['type'] = $type;

                        $item['updated'] = Utils::dateToMoysklad($expected->created);

                        $item[
                            $type === 'product'
                                ? 'article'
                                : 'code'
                        ] = $expected->article;

                        $item['minPrice']['value'] = $expected->prices[2]->value * 100;

                        $salePrice = $item['salePrices'][0];
                        $item['salePrices'] = \array_map(
                            static function ($price) use ($salePrice) {
                                $salePrice['value']             = $price->value * 100;
                                $salePrice['priceType']['name'] = $price->name;

                                $salePrice['priceType']['meta']['href'] = \strtr(
                                    $salePrice['priceType']['meta']['href'], [
                                        '$priceId' => $price->id
                                    ]
                                );

                                return $salePrice;
                            },
                            \array_slice($expected->prices, 0, 2)
                        );

                        $replaces = [
                            '$id'   => $expected->id,
                            '$type' => $type
                        ];

                        $item['meta']['href'] = \strtr(
                            $item['meta']['href'],
                            $replaces
                        );

                        $item['meta']['metadataHref'] = \strtr(
                            $item['meta']['metadataHref'],
                            $replaces
                        );

                        $item['images']['meta']['href'] = \strtr(
                            $item['images']['meta']['href'],
                            $replaces
                        );

                        return $item;
                    },
                    $options['expected']
                );

                $body['meta']['size'] = \count($body['rows']);
                $body['meta']['href'] = (string) $request->getUri();

                return static::createJsonResponse(body: $body);
            },
            'post@1.2/entity/customerorder' => function ($request, $options) {
                $body = static::getResponseData(
                    'post@api.moysklad.ru/api/remap/1.2/entity/customerorder'
                )['body'];

                $item = $body[0];

                $body = \array_map(
                    function ($order) use ($item) {
                        $replaces = [
                            '$id'      => $order->id,
                            '$orderId' => $order->id,
                            '$stateId' => $order->status
                        ];

                        $item['id']           = $order->id;
                        $item['created']      = Utils::dateToMoysklad($order->created);
                        $item['externalCode'] = $order->externalCode;
                        $item['description']  = $order->description;

                        $item['deliveryPlannedMoment'] = $order->shipmentDate
                            ? Utils::dateToMoysklad($order->shipmentDate)
                            : null;

                        $item['state']['meta']['href'] = \strtr(
                            $item['state']['meta']['href'],
                            $replaces
                        );

                        $item['meta']['href'] = \strtr(
                            $item['meta']['href'], $replaces
                        );

                        $item['positions']['meta']['href'] = \strtr(
                            $item['positions']['meta']['href'],
                            $replaces
                        );

                        $item['attributes'] = \array_map(
                            fn ($additional) => static::makeAttribute([
                                'id'       => $additional->id,
                                'name'     => $additional->name,
                                'type'     => \gettype($additional->value),
                                'value'    => $additional->value,
                                'category' => 'customerorder'
                            ]),
                            $order->additionals
                        );

                        $item['positions']['rows'] = \array_map(
                            function ($position) use ($order) {
                                $msPosition = static::makePosition([
                                    'id'       => $position->id,
                                    'entityId' => $order->id,
                                    'quantity' => $position->quantity,
                                    'reserve'  => $position->reserve,
                                    'price'    => $position->price * 100,
                                    'discount' => $position->discount,
                                    'vat'      => $position->vat,
                                    'category' => 'customerorder'
                                ]);

                                $msPosition['assortment'] =
                                    static::makeAssortmentItem([
                                        'id'      => $position->productId,
                                        'article' => $position->article,
                                        'type'    => $position->type
                                    ]);

                                return $msPosition;
                            },
                            $order->positions
                        );

                        return $item;
                    },
                    $options['expected']
                );

                return static::createJsonResponse(body: $body);
            }
        ];

        return static function (RequestInterface $request) use (
            $method,
            $options,
            $methods
        ): ResponseInterface {
            if (isset($options['capture'])) {
                $options['capture']($request, $options);
            }

            $exp    = \explode('@', $method);
            $method = isset($exp[1]) ? $exp[0] : 'get';
            $path = $exp[1] ?? $exp[0];

            return $methods["{$method}@{$path}"]($request, $options);
        };
    }

    /**
     * Создать атрибут.
     *
     * Массив $options принимает:
     *
     * - id:       (string) Идентификатор атрибута
     * - name:     (string) Название атрибута
     * - type:     (string) Тип атрибута (string, boolean, int...)
     * - value:    (mixed)  Значение атрибута
     * - category: (string) Категория атрибута (customerorder, demand...)
     *
     * @param array $options Опции
     *
     * @return array
     */
    protected static function makeAttribute(array $options = []): array
    {
        static $template;

        $options = \array_replace(
            [
                'id'       => static::guidv4(),
                'name'     => 'AttributeName' . \random_int(0, 123),
                'type'     => 'string',
                'value'    => static::fakeString(),
                'category' => 'customerorder'
            ],
            $options
        );

        $attribute = $template ??= static::getResponseData(
            'api.moysklad.ru/api/remap/1.2/entity/$category/metadata/attributes/$id'
        )['body'];

        $replaces = [
            '$id'       => $options['id'],
            '$category' => $options['category']
        ];

        $attribute['id']    = $options['id'];
        $attribute['name']  = $options['name'];
        $attribute['type']  = $options['type'];
        $attribute['value'] = $options['value'];

        $attribute['meta']['href'] = \strtr(
            $attribute['meta']['href'], $replaces
        );

        return $attribute;
    }

    /**
     * Создать элемент ассортимента.
     *
     * @param array $options Опции
     *
     * @return array
     */
    protected static function makeAssortmentItem(array $options = []): array
    {
        static $template;

        $options = \array_replace(
            [
                'id'      => static::guidv4(),
                'article' => static::fakeArticle(),
                'type' => \random_int(0, 3) === 3
                    ? 'product' : 'variant'
            ],
            $options
        );

        $assortment = $template ??= static::getResponseData(
            'api.moysklad.ru/api/remap/1.2/entity/assortment'
        )['body']['rows'][0];

        $replaces = [
            '$id'   => $options['id'],
            '$type' => $options['type']
        ];

        $assortment['id']           = $options['id'];
        $assortment['meta']['type'] = $options['type'];

        $assortment['meta']['href'] = \strtr(
            $assortment['meta']['href'],
            $replaces
        );

        $assortment['meta']['metadataHref'] = \strtr(
            $assortment['meta']['metadataHref'],
            $replaces
        );

        $assortment['images']['meta']['href'] = \strtr(
            $assortment['images']['meta']['href'],
            $replaces
        );

        $keys = $options['type'] === 'product'
            ? ['article', 'code']
            : ['code', 'article'];

        $assortment[$keys[0]] = $options['article'];
        $assortment[$keys[1]] = static::fakeArticle();

        return $assortment;
    }

    /**
     * Распарсить фильтры.
     *
     * @param array $query Запрос
     *
     * @return array<string, array<string>>
     */
    protected static function parseFilters(array $query): array
    {
        if (isset($query['filter'])) {
            $filters = \explode(';',  ($query['filter']));

            $signCases = FilterSign::cases();

            foreach ($filters as $idx => &$filter) {
                foreach ($signCases as $sign) {
                    if (\strpos($filter, $sign->value) === false) {
                        unset($filters[$idx]);
                        continue;
                    }

                    $exp    = \explode($sign->value, $filter);

                    $filter = [
                        'name'  => $exp[0],
                        'sign'  => $sign,
                        'value' => $exp[1]
                    ];

                    break;
                }
            }

            return $filters;
        }

        return [];
    }

    /**
     * Создать позицию.
     *
     * Массив $options принимает:
     *
     * - id:        (string) Идентификатор позиции
     * - entityId:  (string) Идентификатор сущности у которой есть позиция
     * - category:  (string) Название сущности (категории)
     *
     * @param array $options Опции
     *
     * @return array
     */
    protected static function makePosition(array $options = []): array
    {
        static $template;

        $options = \array_replace(
            [
                'id'       => static::guidv4(),
                'entityId' => static::guidv4(),
                'quantity' => $quantity = \random_int(0, 25),
                'reserve'  => \random_int(0, $quantity),
                'price'    => \random_int(0, 1000) * 100,
                'discount' => \random_int(0, 50),
                'vat'      => \random_int(0, 4) === 4,
                'category' => 'customerorder'
            ],
            $options
        );

        $position = $template ??= static::getResponseData(
            'api.moysklad.ru/api/remap/1.2/entity/'
                . '$category/$entityId/positions/$id'
        )['body'];

        $replaces = [
            '$id'       => $options['id'],
            '$entityId' => $options['entityId'],
            '$category' => $options['category']
        ];

        $position['id']       = $options['id'];
        $position['quantity'] = $options['quantity'];
        $position['reserve']  = $options['reserve'];
        $position['vat']      = $options['vat'];
        $position['price']    = $options['price'];
        $position['discount'] = $options['discount'];

        $position['meta']['href'] = \strtr(
            $position['meta']['href'], $replaces
        );

        $position['meta']['type'] = \strtr(
            $position['meta']['type'], $replaces
        );

        $position['meta']['metadataHref'] = \strtr(
            $position['meta']['metadataHref'], $replaces
        );

        return $position;
    }

    /**
     * Получить guid из href
     *
     * @param string $href Ссылка
     *
     * @return string
     */
    protected static function guidFromHref(string $href): string
    {
        $exp = \explode('/', $href);
        return \end($exp);
    }
}
