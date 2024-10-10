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

namespace CashCarryShop\Sizya\Tests\Unit\Moysklad;

use CashCarryShop\Sizya\Moysklad\CustomerOrdersSource;
use CashCarryShop\Sizya\Tests\Traits\OrdersGetterTests;
use CashCarryShop\Sizya\Tests\Traits\OrdersGetterByAdditionalTests;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithMoysklad;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для получения заказов МойСклад.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(CustomerOrdersSource::class)]
class CustomerOrdersSourceTest extends TestCase
{
    use OrdersGetterTests;
    use OrdersGetterByAdditionalTests;
    use InteractsWithMoysklad;

    protected function setUpBeforeTestGetOrders(array $expected): void
    {
        $this->_prepareOrders($expected);

        static::$handler->append(
            static::createMethodResponse('1.2/entity/customerorder', [
                'expected' => $expected
            ])
        );
    }

    protected function setUpBeforeTestGetOrdersByIds(
        array $expectedOrders,
        array $expectedErrors,
        array $expected
    ): void {
        $this->_prepareOrders($expectedOrders);

        static::$handler->append(
            static::createMethodResponse('1.2/entity/customerorder', [
                'expected' => $expectedOrders
            ])
        );
    }

    protected function setUpBeforeTestGetOrdersByAdditional(
        array $expectedOrders,
        array $expectedErrors,
        array $expected
    ): void {
        $this->_prepareOrders($expectedOrders);

        static::$handler->append(
            static::createMethodResponse('1.2/entity/customerorder', [
                'expected' => $expectedOrders
            ])
        );
    }

    protected function createOrdersGetterByAdditional(): CustomerOrdersSource
    {
        return new CustomerOrdersSource([
            'credentials' => ['login', 'password'],
            'client'      => static::createHttpClient(static::$handler)
        ]);
    }

    protected function createOrdersGetter(): CustomerOrdersSource
    {
        return new CustomerOrdersSource([
            'credentials' => ['login', 'password'],
            'client'      => static::createHttpClient(static::$handler)
        ]);
    }

    /**
     * Обработать заказы.
     *
     * @param OrderDTO $orders Заказы
     *
     * @return void
     */
    private function _prepareOrders(array $orders): void
    {
        foreach ($orders as $order) {
            $order->deliveringDate = null;

            foreach ($order->additionals as $additional) {
                $additional->id = $additional->entityId;
            }

            foreach ($order->positions as $position) {
                $position->currency = null;
            }
        }
    }
}
