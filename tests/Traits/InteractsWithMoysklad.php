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
use GuzzleHttp\Exception\RequestException;
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
                $options = \array_replace(
                    [
                        'captureItem'  => static fn () => null,
                        'captureItems' => static fn () => null
                    ],
                    $options
                );

                \parse_str($request->getUri()->getQuery(), $query);

                $field = $options['field'] ?? $query['stockType'] ?? 'quantity';

                if (!isset($options['items'])) {
                    $options['items'] = \array_map(
                        fn () => [
                            'assortmentId' => static::guidv4(),
                            'storeId'      => static::guidv4(),
                            $field         => \random_int(-15, 30)
                        ],
                        \array_fill(0, 200, null)
                    );
                }

                foreach ($options['items'] as $item) {
                    $options['captureItem']($item);
                }

                $options['captureItems']($options['items']);

                return static::createJsonResponse(body: $options['items']);
            },
            'get@1.2/entity/assortment' => function ($request, $options) {
                \parse_str($request->getUri()->getQuery(), $query);

                $options = \array_replace(
                    [
                        'captureItem' => static fn () => null,
                        'captureItems' => static fn () => null
                    ],
                    $options
                );

                $filters = static::parseFilters($query);
                $limit   = $query['limit'] ?? $options['limit'] ?? 100;

                $available = ['id', 'article', 'code'];
                $filtItems = [];
                foreach ($filters as $filter) {
                    if ($filter['sign'] === FilterSign::EQ) {
                        if (\in_array($filter['name'], $available)) {
                            $filtItems[] = [
                                'name'  => $filter['name'],
                                'value' => $filter['value']
                            ];
                        }
                    }
                }
                unset($available);

                $filtItems = \array_unique($filtItems, SORT_REGULAR);

                $body = static::getResponseData(
                    'api.moysklad.ru/api/remap/1.2/entity/assortment'
                )['body'];
                $item = $body['rows'][0];

                $makeItem = function () use ($item, $options) {
                    $type = \random_int(0, 3) === 3
                        ? 'product' : 'variant';

                    $item['id']   = $id = static::guidv4();
                    $item['type'] = $type;

                    $item[
                        $type === 'product'
                            ? 'article'
                            : 'variant'
                    ] = static::fakeArticle();

                    $replaces = [
                        '$id'   => $id,
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
                };

                if ($filtItems) {
                    $items =  \array_map(
                        function ($data) use ($makeItem, $options) {
                            $item = $makeItem();
                            $item[$data['name']] = $data['value'];
                            $options['captureItem']($item);
                            return $item;
                        },
                        $filtItems
                    );
                } else {
                    if (isset($options['items'])) {
                        $items = $options['items'];
                        if (\count($items) > $limit) {
                            $items = \array_slice($items, 0, $limit);
                        }
                    } else {
                        $items = \array_unique(
                            \array_map(
                                function () use ($makeItem, $options) {
                                    $item = $makeItem();
                                    $options['captureItem']($item);
                                    return $item;
                                },
                                \array_fill(0, 100, null)
                            ),
                            SORT_REGULAR
                        );
                    }
                }

                $options['captureItems']($items);

                $body['rows']         = \array_values($items);
                $body['meta']['size'] = \count($items);

                $body['meta']['href'] = (string) $request->getUri();

                return static::createJsonResponse(body: $body);
            },
            'get@1.2/entity/customerorder' => function ($request, $options) {
                \parse_str($request->getUri()->getQuery(), $query);

                $options = \array_replace(
                    [
                        'captureItem' => static fn () => null,
                        'captureItems' => static fn () => null
                    ],
                    $options
                );

                $filters = static::parseFilters($query);

                $available = ['id', 'name'];
                $filtItems = [];
                foreach ($filters as $filter) {
                    if ($filter['sign'] === FilterSign::EQ) {
                        if (\in_array($filter['name'], $available)) {
                            $filtItems[] = [
                                'name'  => $filter['name'],
                                'value' => $filter['value']
                            ];
                            continue;
                        }

                        if (\strpos($filter['name'], 'metadata/attributes')) {
                            $parsed = \explode('/', $filter['name']);

                            $filtItems[] = [
                                'name'   => 'attribute',
                                'attrId' => \end($parsed),
                                'value'  => $filter['value']
                            ];
                        }
                    }
                }
                unset($available);

                $filtItems = \array_unique($filtItems, SORT_REGULAR);

                $body = static::getResponseData(
                    'api.moysklad.ru/api/remap/1.2/entity/customerorder'
                )['body'];

                $item       = $body['rows'][0];
                $attribute  = $item['attributes'][0];
                $expand     = [
                    'positions'  => false,
                    'assortment' => false
                ];

                if (isset($query['expand'])) {
                    $exp = \explode('.', $query['expand']);

                    $expand = [
                        'positions'  => $exp[0] === 'positions',
                        'assortment' => isset($exp[1]) && $exp[1] === 'assortment'
                    ];
                }

                $makeItem = function ($opts = []) use ($item, $expand) {
                    $replaces = [
                        '$id'      => $id = static::guidv4(),
                        '$orderId' => $id
                    ];

                    $item['id']   = $id;
                    $item['name'] = static::fakeArticle();

                    $item['meta']['href'] = \strtr(
                        $item['meta']['href'], $replaces
                    );

                    $item['positions']['meta']['href'] = \strtr(
                        $item['positions']['meta']['href'],
                        $replaces
                    );

                    $item['attributes'][0] = static::makeAttribute([
                        'id'       => $opts['attrId'] ?? static::guidv4(),
                        'category' => 'customerorder',
                        'type'     => 'string',
                        'value'    => $opts['attrValue'] ?? static::fakeString()
                    ]);

                    if ($expand['positions']) {
                        $position = static::makePosition([
                            'entityId' => $id,
                            'category' => 'customerorder'
                        ]);

                        if ($expand['assortment']) {
                            $position['assortment'] = static::makeAssortmentItem();
                        }

                        $item['positions'] = ['rows' => [$position]];
                    }

                    return $item;
                };

                if ($filtItems) {
                    $items = \array_map(
                        function ($data) use ($makeItem, $options) {
                            if ($data['name'] === 'attribute') {
                                $item = $makeItem([
                                    'attrId'    => $data['attrId'],
                                    'attrValue' => $data['value']
                                ]);

                                $options['captureItem']($item);
                                return $item;
                            }

                            $item = $makeItem();
                            $item[$data['name']] = $data['value'];

                            $options['captureItem']($item);
                            return $item;
                        },
                        $filtItems
                    );
                } else {
                    $items = \array_map(
                        function () use ($makeItem, $options) {
                            $item = $makeItem();
                            $options['captureItem']($item);
                            return $item;
                        }, \array_fill(0, 100, null)
                    );
                }

                $options['captureItems']($items);

                $body['rows'] = array_values($items);

                $body['meta']['size'] = \count($items);
                $body['meta']['href'] = (string) $request->getUri();

                return static::createJsonResponse(body: $body);
            },
            'post@1.2/entity/customerorder' => function ($request, $options) {
                \parse_str($request->getUri()->getQuery(), $query);
                $reqBody = \json_decode(
                    $request->getBody()->getContents(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );

                $body = static::getResponseData(
                    'post@api.moysklad.ru/api/remap/1.2/entity/customerorder'
                )['body'];

                $item      = $body[0];
                $attribute = $item['attributes'][0];
                $expand    = [
                    'positions'  => false,
                    'assortment' => false
                ];

                if (isset($query['expand'])) {
                    $exp = \explode('.', $query['expand']);

                    $expand = [
                        'positions'  => $exp[0] === 'positions',
                        'assortment' => isset($exp[1]) && $exp[1] === 'assortment'
                    ];
                }

                $makeItem = function ($received) use ($item) {
                    $replaces = [
                        '$id' => $id = $received['id'] ?? static::guidv4()
                    ];

                    $item['id']      = $id;
                    $item['meta']['href'] = \strtr(
                        $item['meta']['href'], [
                            '$id' => $id
                        ]
                    );

                    $item['updated']      = $received['updated'] ?? static::fakeDate();
                    $item['name']         = $received['name'] ?? static::fakeArticle();
                    $item['moment']       = $received['moment'] ?? static::fakeDate();
                    $item['created']      = $received['created'] ?? static::fakeDate();
                    $item['applicable']   = $received['applicable'] ?? true;
                    $item['vatEnabled']   = $received['vatEnabled'] ?? false;
                    $item['vatInvaluded'] = $received['vatIncluded'] ?? false;
                    $item['published']    = $received['published'] ?? true;
                    $item['printed']      = $received['printed'] ?? false;
                    $item['organization'] = $received['organization'];
                    $item['agent']        = $received['agent'];

                    if (isset($received['state'])) {
                        $item['state'] = $received['state'];
                    }

                    $item['positions']['meta']['href'] = \strtr(
                        $item['positions']['meta']['href'],
                        $replaces
                    );

                    $issetPositions = isset($received['positions']);

                    $item['positins']['size'] = $issetPositions
                        ? \count($received['positions'])
                        : 0;

                    if ($issetPositions && $expand['positions']) {
                        $positions = [];
                        foreach ($received['positions'] as $position) {
                            $created = static::makePosition([
                                'id'       => $position['id'] ?? static::guidv4(),
                                'entityId' => $id,
                                'category' => 'customerorder'
                            ]);

                            if ($expand['assortment']) {
                                $created['assortment'] = static::makeAssortmentItem([
                                    'id' => static::guidFromHref(
                                        $position['assortment']['meta']['href']
                                    ),
                                    'type' => $position['assortment']['meta']['type']
                                ]);
                            }

                            $created['quantity'] = $position['quantity'] ?? 1;
                            $created['price']    = $position['price']
                                ?? (float) \random_int(10, 10000);

                            $created['discount']   = $position['discount'] ?? 0.0;
                            $created['vat']        = $position['vat'] ?? 0;
                            $created['vatEnabled'] = $position['vatEnabled'] ?? false;

                            $created['reserve'] = $created['quantity'];
                            if ($position['reserve']) {
                                $created['reserve'] = $position['reserve'];
                            }

                            $positions[] = $position;
                        }

                        $item['positions'] = ['rows' => $positions];
                    }

                    $item['attributes'] = [];

                    if (isset($received['attributes'])) {
                        foreach ($received['attributes'] as $attribute) {
                            $item['attributes'][] = static::makeAttribute([
                                'id'    => static::guidFromHref(
                                    $attribute['meta']['href']
                                ),
                                'value' => $attribute['value']
                            ]);
                        }
                    }

                    $options['captureItem']($item);

                    return $item;
                };

                $body = isset($reqBody[0])
                    ? $makeItem($reqBody)
                    : \array_map($makeItem, $reqBody);

                $options['captureBody']($body);

                return $body;
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

            $exp    = \exp('@', $method);
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
                'id'   => static::guidv4(),
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

        $assortment['id'] = $options['id'];

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

        $assortment[
            $options['type'] === 'product'
                ? 'article'
                : 'code'
        ] = static::fakeArticle();

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
                'category' => 'customerorder'
            ]
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

        $position['id'] = $options['id'];

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
