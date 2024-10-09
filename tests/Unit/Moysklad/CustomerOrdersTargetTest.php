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

use CashCarryShop\Sizya\OrdersCreatorInterface;
use CashCarryShop\Sizya\OrdersUpdaterInterface;
use CashCarryShop\Sizya\Moysklad\CustomerOrdersTarget;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\OrderUpdateDTO;
use CashCarryShop\Sizya\DTO\OrderCreateDTO;
use CashCarryShop\Sizya\Tests\Traits\OrdersCreatorTests;
use CashCarryShop\Sizya\Tests\Traits\OrdersUpdaterTests;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithMoysklad;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тесты класса для создания/обновления заказов МойСклад.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(CustomerOrdersTarget::class)]
class CustomerOrdersTargetTest extends TestCase
{
    use OrdersCreatorTests;
    use OrdersUpdaterTests;
    use InteractsWithMoysklad;

    protected function setUpBeforeTestMassUpdateOrders(
        array $expected,
        array $forUpdate
    ): void {
        $this->_prepareOrders($expected);

        static::$handler->append(
            static::createMethodResponse('post@1.2/entity/customerorder', [
                'expected' => $expected
            ])
        );
    }

    protected function setUpBeforeTestUpdateOrder(
        OrderDTO       $expected,
        OrderUpdateDTO $forUpdate
    ): void {
        $this->_prepareOrders([$expected]);

        static::$handler->append(
            static::createMethodResponse('post@1.2/entity/customerorder', [
                'expected' => [$expected]
            ])
        );
    }

    protected function setUpBeforeTestCreateOrder(
        OrderDTO       $expected,
        OrderCreateDTO $forCreate
    ): void {
        $this->_prepareOrders([$expected]);

        static::$handler->append(
            static::createMethodResponse('post@1.2/entity/customerorder', [
                'expected' => [$expected]
            ])
        );
    }

    protected function createOrdersUpdater(): OrdersUpdaterInterface
    {
        return new CustomerOrdersTarget([
            'credentials'  => ['login', 'password'],
            'organization' => static::guidv4(),
            'agent'        => static::guidv4(),
            'client'       => static::createHttpClient(static::$handler),
            'limit'        => 100
        ]);
    }

    protected function createOrdersCreator(): ?OrdersCreatorInterface
    {
        return new CustomerOrdersTarget([
            'credentials'  => ['login', 'password'],
            'organization' => static::guidv4(),
            'agent'        => static::guidv4(),
            'client'       => static::createHttpClient(static::$handler),
            'limit'        => 100
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
        }
    }
}
