<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Tests\Unit\Ozon;

use CashCarryShop\Sizya\Ozon\Orders;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithOzon;
use CashCarryShop\Sizya\Tests\Traits\OrdersGetterTests;
use CashCarryShop\Sizya\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для получения заказов Ozon..
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(Orders::class)]
class OrdersTest extends TestCase
{
    use InteractsWithOzon;
    use OrdersGetterTests;

    /**
     * Используемыая сущность.
     *
     * @var ?Orders
     */
    protected static ?Orders $entity = null;

    public static function setUpbeforeClass(): void
    {
        static::$entity = new Orders([
            'token'       => 'token',
            'clientId'    => 123321,
            'unfulfilled' => true,
            'limit'       => 100,
            'client'      => static::createHttpClient(static::$handler)
        ]);
    }

    protected function createOrdersGetter(): ?Orders
    {
        return static::$entity;
    }

    protected function setUpBeforeTestGetOrders(): void
    {
        $template     = static::getResponseData("api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list")['body'];
        $templateItem = $template['result']['postings'][0];

        $productSku = \random_int(100000000, 999999999);
        $template['result']['postings'] = \array_map(
            fn () => static::makePosting([
                'template'   => $templateItem,
                'productSku' => $productSku
            ]),
            \array_fill(0, 100, null)
        );

        $template['count'] = \count($template['result']['postings']);

        static::$handler->append(static::createJsonResponse(body: $template));

        $template = static::getResponseData("api-seller.ozon.ru/v2/product/info/list")['body'];
        $template['result']['items'][0] = static::makeProduct([
            'sku'      => $productSku,
            'template' => $template['result']['items'][0]
        ]);

        static::$handler->append(static::createJsonResponse(body: $template));
    }

    protected function ordersIdsProvider(): array
    {
        [
            'values'   => $ids,
            'provides' => $provides,
            'invalid'  => $invalidIds
        ] = static::generateProvideData();

        $template = static::getResponseData("api-seller.ozon.ru/v3/posting/fbs/get")['body'];

        $productSku = static::guidv4();
        static::$handler->append(...\array_map(
            function ($id) use ($template, $invalidIds, $productSku) {
                if (\in_array($id, $invalidIds)) {
                    return static::createResponse(404, body: 'Posting not found');
                }

                $template['result']['items'] = static::makePosting([
                    'id'         => $id,
                    'productSku' => $productSku,
                    'template'   => $template['result']
                ]);

                return static::createJsonResponse(body: $template);
            },
            $ids
        ));

        $template = static::getResponseData("api-seller.ozon.ru/v2/product/info/list")['body'];
        $template['result']['items'][0] = static::makeProduct([
            'sku'      => $productSku,
            'template' => $template['result']['items'][0]
        ]);

        static::$handler->append(static::createJsonResponse(body: $template));

        return $provides;
    }

    public static function tearDownAfterClass(): void
    {
        static::$entity = null;
    }
}
