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

use GuzzleHttp\Exception\RequestException;
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
     * Изменить GUID у href (если он в конце)
     *
     * @param string $GUID Новый GUID
     * @param string $href Ссылка
     *
     * @return string
     */
    protected static function changeHrefGuid(string $GUID, string $href): string
    {
        $parts = \explode('/', $href);
        \array_pop($parts);
        $parts[] = $GUID;

        return \implode('/', $parts);
    }

    /**
     * Создать Response для запроса получения товаров
     * по идентификаторам МойСклад.
     *
     * @param array $provides   Идентификаторы по чанкам
     * @param array $invalidIds Неправильные идентификаторы
     *
     * @return ResponseInterface[]
     */
    public static function makeProductsGetByIdsResponses(array $provides, array $invalidIds): array
    {
        // Получение шабллонов
        $template    = static::getResponseData("api.moysklad.ru/api/remap/1.2/entity/assortment")['body'];
        $templateRow = $template['rows'][0];

        $makeRow = function ($id) use ($templateRow, $invalidIds) {
            if (\in_array($id, $invalidIds)) {
                return null;
            }

            return static::makeAssortmentItem([
                'id'       => $id,
                'template' => $templateRow
            ]);
        };

        $makeResponse = function ($ids) use ($template, $makeRow) {
            $template['rows'] = \array_filter(
                \array_map($makeRow, $ids),
                'is_array'
            );

            $template['meta']['size'] = \count($template['rows']);

            return function ($request) use ($template) {
                $template['meta']['href'] = (string) $request->getUri();
                return static::createJsonResponse(body: $template);
            };
        };

        return \array_map($makeResponse, $provides);
    }

    /**
     * Создать заказ покупателя.
     *
     * Массив $options принимает:
     *
     *  - id                 (string) Идентификатор заказа покупателя
     *  - template           (array)  Шаблон
     *  - positionTemplate   (?array) Шаблон позициии
     *  - assortmentTemplate (?array) Шаблон элемента ассортимента
     *  - attributeTemplate  (?array) Шаблон атрибута
     *
     * @return array
     */
    protected static function makeCustomerOrder(array $options): array
    {
        $options = \array_replace(
            [
                'id'                 => static::guidv4(),
                'article'            => static::fakeArticle(),
                'attributeId'        => static::guidv4(),
                'attributeValue'     => static::fakeString(),
                'positionTemplate'   => null,
                'assortmentTemplate' => null,
                'attributeTemplate'  => null
            ],
            $options
        );

        $options['template']['id']   = $options['id'];
        $options['template']['name'] = $options['article'];

        $options['template']['positions']['meta']['href'] = \str_replace(
            '$id',
            $options['id'],
            $options['template']['positions']['meta']['href']
        );

        if ($options['positionTemplate']) {
            $options['positionTemplate']['meta']['href'] = \str_replace(
                '$orderId',
                $options['id'],
                $options['positionTemplate']['meta']['href']
            );

            $options['template']['positions'] = [
                'rows' => [
                    static::makePosition([
                        'template'           => $options['positionTemplate'],
                        'assortmentTemplate' => $options['assortmentTemplate']
                    ])
                ]
            ];
        }

        if ($options['attributeTemplate']) {
            $options['template']['attributes'] = [
                static::makeAttributeItem([
                    'id'       => $options['attributeId'],
                    'value'    => $options['attributeValue'],
                    'template' => $options['attributeTemplate']
                ])
            ];
        }

        return $options['template'];
    }

    /**
     * Создать позицию.
     *
     * Массив $options принимает:
     *
     * - id:                (string) Идентификатор позиции
     * - category:          (string) Категорию позиции
     * - entityId:          (string) Идентификатор сущности позиции
     * - template:          (array)  Шаблон позиции
     * - assortmentId       (string) Идентификатор ассортимента
     * - assortmentArticle  (string) Артикул ассортимента
     * - assortmentType     (string) Тип ассортимента
     * - assortmentTemplate (?array) Шаблон ассортимента
     *
     * @return array
     */
    protected static function makePosition(array $options): array
    {
        $options = \array_replace(
            [
                'id'                 => static::guidv4(),
                'category'           => 'customerorder',
                'entityId'           => static::guidv4(),
                'assortmentId'       => static::guidv4(),
                'assortmentArticle'  => static::fakeArticle(),
                'assortmentType'     => 'product',
                'assortmentTemplate' => null
            ],
            $options
        );

        $options['template']['id'] = $options['id'];

        $options['template']['meta']['href'] = \strtr(
            $options['template']['meta']['href'], [
                '$id'       => $options['id'],
                '$entityId' => $options['entityId'],
                '$category' => $options['category']
            ]
        );

        $options['template']['meta']['type'] = \str_replace(
            '$category',
            $options['category'],
            $options['template']['meta']['metadataHref']
        );

        $options['template']['meta']['metadataHref'] = \str_replace(
            '$category',
            $options['category'],
            $options['template']['meta']['metadataHref']
        );

        if ($options['assortmentTemplate']) {
            $options['template']['assortment'] = static::makeAssortmentItem([
                'id'       => $options['assortmentId'],
                'type'     => $options['assortmentType'],
                'article'  => $options['assortmentArticle'],
                'template' => $options['assortmentTemplate']
            ]);

            return $options['template'];
        }

        if (isset($options['template']['assortment'])) {
            $options['template']['assortment']['meta']['href'] = \strtr(
                $options['template']['assortment']['meta']['href'], [
                    'id'   => $options['assortmentId'],
                    'type' => $options['assortmentType']
                ]
            );

            $options['template']['assortment']['meta']['href'] = \str_replace(
                '$type',
                $options['assortmentType'],
                $options['template']['assortment']['meta']['href']
            );
        }

        return $options['template'];
    }

    /**
     * Создать элемент ассортимента.
     *
     * Массив $options принимает:
     *
     * - id:       (string) Идентификатор ассортимента
     * - article:  (string) Название ассортимента
     * - type:     (string) Тип ассортимента
     * - template: (array)  Шаблон для ассортимента
     *
     * @param array $options Опции
     *
     * @return array
     */
    protected static function makeAssortmentItem(array $options): array
    {
        $options = \array_replace(
            [
                'id'      => static::guidv4(),
                'article' => static::fakeArticle(),
                'code'    => static::fakeArticle(),
                'type'    => 'product'
            ],
            $options
        );

        $options['template']['id']           = $options['id'];
        $options['template']['meta']['type'] = $options['type'];

        if ($options['type'] === 'variant') {
            unset($options['template']['article']);
            $options['template']['code'] = $options['code'];
        } else if ($options['type'] === 'product') {
            unset($options['template']['code']);
            $options['template']['article'] = $options['article'];
        }

        $options['template']['meta']['href'] = \strtr(
            $options['template']['meta']['href'], [
                '$id'   => $options['id'],
                '$type' => $options['type']
            ]
        );

        $options['template']['meta']['metadataHref'] = \strtr(
            $options['template']['meta']['metadataHref'], [
                '$id'   => $options['id'],
                '$type' => $options['type']
            ]
        );

        $options['template']['images']['meta']['href'] = \strtr(
            $options['template']['images']['meta']['href'], [
                '$id'   => $options['id'],
                '$type' => $options['type']
            ]
        );

        return $options['template'];
    }

    /**
     * Создать атрибут
     *
     * Массив $options принимает:
     *
     * - id:       (string) Идентификатор атрибута
     * - type:     (string) Тип атрибута
     * - category: (string) Категорию атрибута
     * - value:    (mixed)  Значение атрибута
     * - template: (array)  Шаблон атрибута
     *
     * @param array $options Опции
     *
     * @return array
     */
    protected static function makeAttributeItem(array $options): array
    {
        $options = \array_replace(
            [
                'id'       => static::guidv4(),
                'article'  => static::fakeArticle(),
                'value'    => static::fakeString(),
                'type'     => 'string',
                'category' => 'customerorder'
            ],
            $options
        );

        $options['template']['meta']['href'] = \strtr(
            $options['template']['meta']['href'], [
                'id'       => $options['id'],
                'category' => $options['category']
            ]
        );

        $options['template']['id']    = $options['id'];
        $options['template']['type']  = $options['type'];
        $options['template']['value'] = $options['value'];

        return $options['template'];
    }

    /**
     * Создать элемент короткого отчета об остатках.
     *
     * Массив $options принимает:
     *
     * - id:        (string) Идентификатор ассортимента
     * - storeId:   (string) Идентификатор склада
     * - stockType: (string) Тип остатков
     * - template:  (string) Шаблон
     *
     * @param array $options Опции
     *
     * @return array
     */
    protected function createShortStock(array $options): array
    {
        $options = \array_replace(
            [
                'id'        => static::guidv4(),
                'storeId'   => static::guidv4(),
                'quantity'  => \random_int(-5, 30),
                'stockType' => 'quantity'
            ],
            $options
        );

        $options['template']['storeId']             = $options['storeId'];
        $options['template']['assortmentId']        = $options['id'];
        $options['template'][$options['stockType']] = $options['quantity'];

        return $options['template'];
    }

    /**
     * Проверить стоит ли запускать тест, проверая response от МойСклад.
     *
     * @param callable          $resolve Функция для вызова и возвращения значения
     * @param ?callable(string) $catch   Перехватить ошибку
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
