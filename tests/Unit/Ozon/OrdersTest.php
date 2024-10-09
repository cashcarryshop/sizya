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
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithOzon;
use CashCarryShop\Sizya\Tests\Traits\OrdersGetterTests;
use PHPUnit\Framework\TestCase;
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

    protected function createOrdersGetter(): Orders
    {
        return new Orders([
            'token'       => 'token',
            'clientId'    => 123321,
            'unfulfilled' => true,
            'limit'       => 100,
            'client'      => static::createHttpClient(static::$handler)
        ]);
    }

    protected function setUpBeforeTestGetOrders(array $expected): void
    {
        $this->_prepareOrders($expected);

        static::$handler->append(
            static::createMethodResponse('v3/posting/fbs/unfulfilled/list', [
                'expected' => $expected
            ])
        );

        $products = [];
        foreach ($expected as $order) {
            foreach ($order->positions as $position) {
                if (isset($products[$position->productId])) {
                    continue;
                }

                $products[$position->productId] = static::fakeProductDto([
                    'id'      => $position->productId,
                    'article' => $position->article
                ]);
            }
        }

        static::$handler->append(
            static::createMethodResponse('v2/product/info/list', [
                'expected' => \array_values($products)
            ])
        );
    }

    protected function setUpBeforeTestGetOrdersByIds(
        array $expectedOrders,
        array $expectedErrors,
        array $expected
    ): void {
        $this->_prepareOrders($expectedOrders);

        $products = [];
        foreach ($expected as $item) {
            if ($item instanceof OrderDTO) {
                static::$handler->append(
                    static:createMethodResponse(
                        'v3/posting/fbs/get', [
                            'expected' => $item
                        ]
                    )
                );

                foreach ($item->positions as $position) {
                    if (isset($products[$position->productId])) {
                        continue;
                    }

                    $products[$position->productId] = static::fakeProductDto([
                        'id'      => $position->productId,
                        'article' => $position->article
                    ]);
                }

                continue;
            }

            if ($item->type === ByErrorDTO::NOT_FOUND) {
                static::$handler->append(static::createResponse(code: 404));
            }
        }

        static::$handler->append(
            static::createMethodResponse('v2/product/info/list', [
                'expected' => \array_values($products)
            ])
        );
    }

    /**
     * Обработать заказы
     *
     * @param OrderDTO[] $orders
     *
     * @return void
     */
    private function _prepareOrders(array $orders): void
    {
        foreach ($orders as $order) {
            $order->additionals = [];

            foreach ($order->positions as $position) {
                $position->type      = 'product';
                $position->productId = (string) \random_int(100000000, 999998888);
            }
        }
    }
}
