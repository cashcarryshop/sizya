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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Трейт с методами для работы с Ozon классами.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see OrdersGetterByAdditionalInterface
 */
trait InteractsWithOzon
{
    use InteractsWithHttp;
    use GetResponseDataTrait;

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
            'v2/product/list' => function ($request, $options) {
                $body = \json_decode(
                    $request->getBody()->getContents(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                $limit = $options['limit'] ?? 1000;

                if (!isset($options['items'])) {
                    $options['items'] = \array_map(
                        fn () => [
                            'id'      => \random_int(100000000, 999999999),
                            'article' => static::fakeArticle()
                        ],
                        \array_fill(0, $limit > 1000 ? 1000 : $limit, null)
                    );
                }

                $body = static::getResponseData('api-seller.ozon.ru/v2/product/list')['body'];

                $body['result']['items'] = \array_map(
                    static fn ($item) => [
                        'product_id' => (int) $item['id'] ?? \random_int(100000000, 999999999),
                        'offer_id'   => $item['article'] ?? static::fakeArticle()
                    ],
                    $options['items']
                );

                $body['total'] = \count($body['result']['items']);

                return static::createJsonResponse(body: $body);
            },
            'v2/product/info/list' => function ($request, $options) {
                $reqBody = \json_decode(
                    $request->getBody()->getContents(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                $options = \array_replace(
                    [
                        'captureItem'  => static fn () => null,
                        'captureItems' => static fn () => null,
                        'notFound'     => []
                    ],
                    $options
                );

                $body = static::getResponseData('api-seller.ozon.ru/v2/product/info/list')['body'];

                $templateItem = $body['result']['items'][0];

                $idRelation = ['product_id' => 'id'];

                $items = [];
                foreach ($reqBody as $key => $searches) {
                    $searches = \array_unique($searches);

                    foreach ($searches as $value) {
                        if (\in_array($value, $options['notFound'])) {
                            continue;
                        }

                        $item = $templateItem;

                        $item['id']       = \random_int(100000000, 999999999);
                        $item['offer_id'] = static::fakeArticle();
                        $item['sku']      = \random_int(100000000, 999999999);

                        $item[$idRelation[$key] ?? $key] = $value;
                        $options['captureItem']($item);

                        $items[] = $item;
                    }
                }

                $options['captureItems']($items);

                $body['result']['items'] = \array_values($items);
                $body['result']['count'] = \count($items);

                return static::createJsonResponse(body: $body);
            },
            'v3/posting/fbs/unfulfilled/list' => function ($request, $options) {
                $reqBody = \json_decode(
                    $request->getBody()->getContents(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                $limit = $options['limit'] ?? 1000;
                if (!isset($options['items'])) {
                    $options['items'] = \array_map(
                        static fn () => [
                            'id'              => static::guidv4(),
                            'article'         => \random_int(100000000, 999999999),
                            'productSku'      => \random_int(100000000, 999999999),
                            'productArticle'  => static::fakeArticle(),
                            'productQuantity' => \random_int(0, 10)
                        ],
                        \array_fill(0, $limit > 1000 ? 1000 : $limit, null)
                    );
                }

                $body = static::getResponseData('api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list')['body'];
                $templatePosting = $body['result']['postings'][0];

                foreach ($options['items'] as $item) {
                    $posting = $templatePosting;

                    $posting['posting_number'] = $item['id'] ?? static::guidv4();
                    $posting['offer_id']       = $item['article'] ?? static::fakeArticle();

                    $posting['products'][0]['quantity'] = $item['productQuantity'] ?? \random_int(0, 10);
                    $posting['products'][0]['offer_id'] = $item['productArticle'] ?? static::fakeArticle();
                    $posting['products'][0]['sku'] = $item['productSku'] ?? \random_int(100000000, 999999999);

                    $body['result']['postings'][] = $posting;
                }

                return static::createJsonResponse(body: $body);
            },
            'v3/posting/fbs/get' => function ($request, $options) {
                $postingNumber = \json_decode(
                    $request->getBody()->getContents(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                )['posting_number'];

                $options = \array_replace(
                    [
                        'captureItem' => static fn () => null
                    ],
                    $options
                );

                $body = static::getResponseData('api-seller.ozon.ru/v3/posting/fbs/get')['body'];

                $result                   = $body['result'];
                $result['posting_number'] = $postingNumber;

                $options['captureItem']($result);

                $body['result'] = $result;

                return static::createJsonResponse(body: $body);
            },
            'v1/product/info/stocks-by-warehouse/fbs' => function ($request, $options) {
                $skus = \array_unique(
                    \json_decode(
                        $request->getBody()->getContents(),
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    )
                )['sku'];

                $options = \array_replace(
                    [
                        'captureItem' => static fn () => null
                    ],
                    $options
                );

                $body = static::getResponseData(
                    'api-seller.ozon.ru/v1/product/info/stocks-by-warehouse/fbs'
                )['body'];

                $body['result'] = \array_map(
                    function ($sku) use ($options) {
                        $stock = [
                            'sku'            => $sku,
                            'fbs_sku'        => $sku,
                            'present'        => \random_int(0, 10),
                            'reserved'       => \random_int(0, 10),
                            'warehouse_id'   => \random_int(100000000, 999999999),
                            'warehouse_name' => static::fakeArticle()
                        ];

                        $options['captureItem']($stock);

                        return $stock;
                    },
                    $skus
                );

                return static::createJsonResponse(body: $body);
            },
            'v2/products/stocks' => function ($request, $options) {
                $reqBody = \json_decode(
                    $request->getBody()->getContents(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );

                $options = \array_replace(
                    [
                        'captureItems' => static fn () => null,
                    ],
                    $options
                );

                $body = static::getResponseData(
                    'api-seller.ozon.ru/v2/products/stocks'
                )['body'];

                foreach ($reqBody['stocks'] as $stock) {
                    $body['result'] = [
                        'warehouse_id' => $stock['warehouse_id'],
                        'offer_id'     => $stock['offer_id'] ?? static::fakeArticle(),
                        'updated'      => true,
                        'errors'       => [],
                        'product_id'   => $stock['product_id']
                            ?? \random_int(100000000, 999999999),
                    ];
                }

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

            return $methods[$method]($request, $options);
        };
    }
}
