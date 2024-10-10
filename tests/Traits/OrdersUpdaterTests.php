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

use CashCarryShop\Sizya\OrdersUpdaterInterface;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\OrderUpdateDTO;
use CashCarryShop\Sizya\DTO\AdditionalUpdateDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;

/**
 * Трейт с тестами обновления заказов.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see OrdersUpdaterInterface
 */
trait OrdersUpdaterTests
{
    use InteractsWithFakeData;
    use OrdersAssertions;

    public function testMassUpdateOrders(): void
    {
        $updater = $this->createOrdersUpdater();

        $expected = \array_map(
            fn () => static::fakeOrderDto(),
            \array_fill(0, 10, null)
        );

        $forUpdate = \array_map(
            fn ($order) => static::fakeOrderUpdateDtoFromOrder($order),
            $expected
        );

        $this->setUpBeforeTestMassUpdateOrders($expected, $forUpdate);

        $this->assertOrders($expected, $updater->massUpdateOrders($forUpdate));
    }

    public function testUpdateOrder(): void
    {
        $updater = $this->createOrdersUpdater();

        $expected  = static::fakeOrderDto();
        $forUpdate = static::fakeOrderUpdateDtoFromOrder($expected);

        $this->setUpBeforeTestUpdateOrder($expected, $forUpdate);

        $this->assertOrders([$expected], [$updater->updateOrder($forUpdate)]);
    }

    protected function setUpBeforeTestMassUpdateOrders(
        array $expected,
        array $forUpdate
    ): void {
        // ...
    }

    protected function setUpBeforeTestUpdateOrder(
        OrderDTO       $expected,
        OrderUpdateDTO $forUpdate
    ): void {
        // ...
    }

    abstract protected function createOrdersUpdater(): OrdersUpdaterInterface;
}
