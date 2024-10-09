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
 * Тестирования класса для получения заказов Ozon.
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
        $productSku = \random_int(100000000, 999999999);
        static::$handler->append(static::createMethodResponse('v3/posting/fbs/unfulfilled/list', [
            'items' => \array_map(
                static fn () => ['productSku' => $productSku],
                \array_fill(0, 100, null)
            )
        ]));

        $productId = \random_int(100000000, 999999999);
        static::$handler->append(static::createMethodResponse('v2/product/info/list', [
            'captureItem' => static function (&$item) use ($productSku, $productId) {
                $item['sku'] = $productSku;
                $item['id']  = $productId;
            }
        ]));
    }

    protected function ordersIdsProvider(): array
    {
        [
            'provides' => $provides,
            'invalid'  => $invalidIds
        ] = static::generateProvideData();

        $productSku = static::guidv4();
        foreach ($provides as $ids) {
            foreach ($ids as $id) {
                if (\in_array($id, $invalidIds)) {
                    static::$handler->append(static::createResponse(code: 404));
                    continue;
                }

                static::$handler->append(
                    static::createMethodResponse('v3/posting/fbs/get', [
                        'captureItem' => static function (&$result) use ($productSku) {
                            $result['products'][0]['sku'] = $productSku;
                        }
                    ])
                );
            }

            static::$handler->append(
                static::createMethodResponse('v2/product/info/list', [
                    'captureItem' => static function (&$item) use ($productSku) {
                        $item['sku'] = $productSku;
                    }
                ])
            );
        }

        return $provides;
    }

    public static function tearDownAfterClass(): void
    {
        static::$entity = null;
    }
}
