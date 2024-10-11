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

use CashCarryShop\Sizya\Ozon\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

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
                $body = static::getResponseData(
                    'api-seller.ozon.ru/v2/product/list'
                )['body'];

                $body['result']['items'] = \array_map(
                    static fn ($product) => [
                        'product_id' => $product->id,
                        'offer_id'   => $product->article
                    ],
                    $options['expected']
                );

                $body['result']['total'] = \count($body['result']['items']);

                return static::createJsonResponse(body: $body);
            },
            'v2/product/info/list' => function ($request, $options) {
                $body = static::getResponseData(
                    'api-seller.ozon.ru/v2/product/info/list'
                )['body'];

                $item = $body['result']['items'][0];

                $body['result']['items'] = \array_map(
                    static function ($product) use ($item) {
                        $item['id']         = $id = (int) $product->id;
                        $item['offer_id']   = $product->article;
                        $item['sku']        = $id + 10;
                        $item['created_at'] = $product->created;
                        $item['price']      = (string) $product->prices[0]->value;
                        $item['min_price']  = (string) $product->prices[1]->value;

                        return $item;
                    },
                    $options['expected']
                );

                $body['result']['count'] = \count($body['result']['items']);

                return static::createJsonResponse(body: $body);
            },
            'v3/posting/fbs/unfulfilled/list' => function ($request, $options) {
                $body = static::getResponseData('api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list')['body'];
                $posting = $body['result']['postings'][0];

                $body['result']['postings'] = \array_map(
                    function ($order) use ($posting) {
                        $posting['posting_number'] = $order->id;
                        $posting['status']         = $order->status;
                        $posting['substatus']      = $order->status;
                        $posting['in_process_at'] = Utils::dateToOzon($order->created);

                        $posting['shipment_date'] =
                            $order->shipmentDate
                            ? Utils::dateToOzon($order->shipmentDate)
                            : null;

                        $posting['delivering_date'] =
                            $order->deliveringDate
                            ? Utils::dateToOzon($order->deliveringDate)
                            : null;

                        $product = $posting['products'][0];
                        $posting['products'] = \array_map(
                            static function ($position) use ($product) {
                                $product['price']         = (string) $position->price;
                                $product['offer_id']      = $position->article;
                                $product['quantity']      = (int) $position->quantity;
                                $product['currency_code'] = $position->currency;
                                $product['sku'] = ((int) $position->productId) + 10;

                                return $product;
                            },
                            $order->positions
                        );

                        return $posting;
                    },
                    $options['expected']
                );

                $body['result']['count'] = \count($body['result']['postings']);

                return static::createJsonResponse(body: $body);
            },
            'v3/posting/fbs/get' => function ($request, $options) {
                $body = static::getResponseData('api-seller.ozon.ru/v3/posting/fbs/get')['body'];

                $body['result']['posting_number'] = $options['expected']->id;
                $body['result']['status']         = $options['expected']->status;
                $body['result']['substatus']      = $options['expected']->status;
                $body['result']['in_process_at'] =
                    Utils::dateToOzon($options['expected']->created);


                $body['result']['shipment_date'] =
                    $options['expected']->shipmentDate
                    ? Utils::dateToOzon($options['expected']->shipmentDate)
                    : null;

                $body['result']['delivering_date'] =
                    $options['expected']->deliveringDate
                    ? Utils::dateToOzon($options['expected']->deliveringDate)
                    : null;

                $product = $body['result']['products'][0];
                $body['result']['products'] = \array_map(
                    static function ($position) use ($product) {
                        $product['currency_code'] = $position->currency;
                        $product['price']         = (string) $position->price;
                        $product['offer_id']      = $position->article;
                        $product['sku']           = ((int) $position->productId) + 10;
                        $product['quantity']      = $position->quantity;

                        return $product;
                    },
                    $options['expected']->positions
                );

                return static::createJsonResponse(body: $body);
            },
            'v1/product/info/stocks-by-warehouse/fbs' => function ($request, $options) {
                $body = static::getResponseData(
                    'api-seller.ozon.ru/v1/product/info/stocks-by-warehouse/fbs'
                )['body'];

                $body['result'] = \array_map(
                    fn ($stock) => [
                        'sku'            => ((int) $stock->id) + 10,
                        'fbs_sku'        => ((int) $stock->id) + 10,
                        'present'        => $stock->quantity,
                        'reserved'       => \random_int(0, $stock->quantity),
                        'warehouse_id'   => (int) $stock->warehouseId,
                        'warehouse_name' => static::fakeArticle()
                    ],
                    $options['expected']
                );

                return static::createJsonResponse(body: $body);
            },
            'v2/products/stocks' => function ($request, $options) {
                $body = static::getResponseData(
                    'api-seller.ozon.ru/v2/products/stocks'
                )['body'];

                $body['result'] = \array_map(
                    static fn ($stock) => [
                        'warehouse_id' => (int) $stock->warehouseId,
                        'product_id'   => (int) $stock->id,
                        'offer_id'     => $stock->article,
                        'updated'      => true,
                        'errors'       => []
                    ],
                    $options['expected']
                );

                return static::createJsonResponse(body: $body);
            },
            'v4/product/info/prices' => function ($request, $options) {
                $body = static::getResponseData(
                    'api-seller.ozon.ru/v4/product/info/prices'
                )['body'];

                $item = $body['result']['items'][0];
                $body['result']['items'] = \array_map(
                    function ($productPrices) use ($item) {
                        $item['product_id']     = (int) $productPrices->id;
                        $item['offer_id']       = $productPrices->article;
                        $item['price']['price'] = $productPrices->prices[0]->value;

                        $item['price']['marketing_price'] =
                            $productPrices->prices[1]->value;

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

            return $methods[$method]($request, $options);
        };
    }
}
