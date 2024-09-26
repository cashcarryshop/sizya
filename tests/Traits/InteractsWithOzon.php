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

    /**
     * Создать Response для получения товаров.
     *
     * @param array  $ids  Идентификаторы товаров
     * @param ?array $skus SKU Товаров
     *
     * @return ResponseInterface[]
     */
    protected static function makeProductsGetResponses(array $ids, ?array $skus = null): array
    {
        if ($skus === null) {
            $skus = \array_map(
                static fn () => \random_int(100000000, 999999999),
                $ids
            );
        }

        $template    = static::getResponseData("api-seller.ozon.ru/v2/product/list")['body'];
        $templateRow = $template['result']['items'][0];

        $template['result']['items'] = \array_map(
            fn ($id, $sku) => static::makeListProduct([
                'id'       => $id,
                'sku'      => $sku,
                'template' => $templateRow
            ]),
            $ids,
            $skus
        );

        $template['result']['total'] = \count($template['result']['items']);

        return \array_merge(
            [static::createJsonResponse(body: $template)],
            static::makeProductsGetByIdsResponses([$ids], [])
        );
    }

    /**
     * Создать Response для запроса получения товаров
     * по идентификаторам.
     *
     * @param array $provides   Идентификаторы по чанкам
     * @param array $invalidIds Неправильные идентификаторы
     *
     * @return ResponseInterface[]
     */
    public static function makeProductsGetByIdsResponses(array $provides, array $invalidIds): array
    {
        // Получение шабллонов
        $template    = static::getResponseData("api-seller.ozon.ru/v2/product/info/list")['body'];
        $templateRow = $template['result']['items'][0];

        $makeRow = function ($id) use ($templateRow, $invalidIds) {
            if (\in_array($id, $invalidIds)) {
                return null;
            }

            return static::makeProduct([
                'id'       => $id,
                'template' => $templateRow
            ]);
        };

        $makeResponse = function ($ids) use ($template, $makeRow) {
            $template['result']['items'] = \array_filter(
                \array_map($makeRow, $ids),
                'is_array'
            );

            return static::createJsonResponse(body: $template);
        };

        return \array_map(fn ($ids) => $makeResponse($ids), $provides);
    }

    /**
     * Создать элемент метода получения unfulfilled отправлений.
     *
     * Массив $options принимает:
     *
     * - id:              (int)    Идентификатор заказа
     * - article:         (string) Артикул товара
     * - template:        (array)  Шаблон отправления
     * - productId        (int)    Идентификатор товара
     * - productArticle   (string) Артикул товара
     * - productPrice     (int)    Цена товара
     * - productQuantity  (int)    Количество товара
     *
     * @param array $options Опции
     *
     * @return array
     */
    protected static function makePosting(array $options): array
    {
        $options = \array_replace(
            [
                'id'              => \random_int(100000000, 999999999),
                'article'         => static::fakeArticle(),
                'productSku'      => \random_int(100000000, 999999999),
                'productArticle'  => static::fakeArticle(),
                'productPrice'    => \random_int(100, 1000),
                'productQuantity' => \random_int(1, 10)
            ],
            $options
        );

        $options['template']['posting_number'] = (string) $options['id'];
        $options['template']['article']        = $options['article'];

        $options['template']['products'][0]['sku']      = $options['productSku'];
        $options['template']['products'][0]['offer_id'] = $options['productArticle'];
        $options['template']['products'][0]['price']    = (string) $options['productPrice'];
        $options['template']['products'][0]['quantity'] = $options['productQuantity'];

        return $options['template'];
    }

    /**
     * Создать товар из product/list.
     *
     * Массив $options принимает:
     *
     * - id:      (int)    Идентификатор товара
     * - article: (string) Артикул товара
     *
     * @param array $options Опции
     *
     * @return array
     */
    protected static function makeListProduct(array $options): array
    {
        $options = \array_replace(
            [
                'id'      => \random_int(100000000, 999999999),
                'article' => static::fakeArticle()
            ],
            $options
        );

        $options['template']['product_id'] = $options['id'];
        $options['template']['offer_id']   = $options['article'];

        return $options['template'];
    }

    /**
     * Создать товар из product/info/list
     *
     * Массив $options принимает:
     *
     * - id:      (int)    Идентификатор товара
     * - sku:     (int)    Sku товара
     * - article: (string) Артикул товара
     * - price:   (float)  Цена
     *
     * @param array $options Опции
     *
     * @return array
     */
    protected static function makeProduct(array $options): array
    {
        $options = \array_replace(
            [
                'id'      => \random_int(100000000, 999999999),
                'sku'     => \random_int(100000000, 999999999),
                'article' => static::fakeArticle(),
                'price'   => (float) \random_int(100, 1000)
            ],
            $options
        );

        $options['template']['id']       = $options['id'];
        $options['template']['sku']      = $options['sku'];
        $options['template']['offer_id'] = $options['article'];
        $options['template']['price']    = (string) $options['price'];

        return $options['template'];
    }

    /**
     * Создать товар из product/info/list
     *
     * Массив $options принимает:
     *
     * - id:      (int)    Идентификатор товара
     * - sku:     (int)    Sku товара
     * - article: (string) Артикул товара
     * - price:   (float)  Цена
     *
     * @param array $options Опции
     *
     * @return array
     */
    protected static function makeByWarehouseStock(array $options): array
    {
        $options = \array_replace(
            [
                'id'          => \random_int(100000000, 999999999),
                'sku'         => \random_int(100000000, 999999999),
                'quantity'    => \random_int(0, 10),
                'reserved'    => \random_int(0, 10),
                'warehouseId' => \random_int(100000000, 999999999)
            ],
            $options
        );

        $options['template']['product_id']   = $options['id'];
        $options['template']['sku']          = $options['sku'];
        $options['template']['fbs_sku']      = $options['sku'];
        $options['template']['quantity']     = $options['quantity'];
        $options['template']['reserved']     = $options['reserved'];
        $options['template']['warehouse_id'] = $options['warehouseId'];

        return $options['template'];
    }

    /**
     * Проверить стоит ли запускать тест, проверая response от МойСклад.
     *
     * @param callable          $resolve Функция для вызова и возвращения значения
     * @param ?callable(string) $catch   Перехватить ошибку
     *
     *
     * @return mixed Результат из
     */
    protected static function markSkippedIfBadResponse(callable $resolve, ?callable $catch = null): mixed
    {
        $catch ??= fn ($message) => static::markTestSkipped($message);

        if (static::$credentials) {
            try {
                return $resolve(static::$credentials);
            } catch (RequestException $exception) {
                $response = $exception->getResponse();

                if (\in_array($response->getStatusCode(), [401, 403])) {
                    $catch(
                        \sprintf(
                            'Invalid credentials. cannot complete test. Code: [%d], Body: [%s]',
                            $response->getStatusCode(),
                            $response->getBody()->getContents()
                        )
                    );

                    return null;
                }


                $catch(
                    \sprintf(
                        'Moysklad request error. Code [%d], Body [%s]',
                        $response->getStatusCode(),
                        $response->getBody()->getContents()
                    )
                );
                return null;
            }
        }

        $catch(
            'Credentials not set. Cannot complete test. '
                . 'Env variables MOYSKLAD_LOGIN, MOYSKLAD_PASSWORD '
                . 'or MOYSKLAD_TOKEN is required'
        );
    }
}
