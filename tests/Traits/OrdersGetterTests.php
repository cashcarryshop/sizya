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

use CashCarryShop\Sizya\OrdersGetterInterface;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\AdditionalDTO;
use CashCarryShop\Sizya\DTO\PositionDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;

/**
 * Трейт с тестами получения заказов.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see OrdersGetterInterface
 */
trait OrdersGetterTests
{
    use InteractsWithFakeData;
    use OrdersAssertions;

    public function testGetOrders(): void
    {
        $getter = $this->createOrdersGetter();

        $expected = \array_map(
            fn () => static::fakeOrderDto(),
            \array_fill(0, 10, null)
        );

        $this->setUpBeforeTestGetOrders($expected);

        $this->assertOrders($expected, $getter->getOrders());
    }

    public function testGetOrdersByIds(): void
    {
        [
            'values'  => $ids,
            'valid'   => $validIds,
            'invalid' => $invalidIds
        ] = static::generateFakeData();

        $getter = $this->createOrdersGetter();

        $expectedOrders = [];
        $expectedErrors = [];
        $expected       = \array_map(
            function ($id) use (
                $invalidIds,
                &$expectedOrders,
                &$expectedErrors
            ) {
                if (\in_array($id, $invalidIds)) {
                    return $expectedErrors[] = ByErrorDTO::fromArray([
                        'type'  => ByErrorDTO::NOT_FOUND,
                        'value' => $id
                    ]);
                }

                return $expectedOrders[] = static::fakeOrderDto(['id' => $id]);
            },
            $ids
        );

        $this->setUpBeforeTestGetOrders(
            $expectedOrders,
            $expectedErrors,
            $expected
        );

        $this->assertOrders($expected, $getter->getOrders());
    }

    abstract protected function createOrdersGetter(): OrdersGetterInterface;

    protected function setUpBeforeTestGetOrders(array $expected): void
    {
        // ...
    }

    protected function setUpBeforeTestGetOrdersByIds(
        array $expectedOrders,
        array $expectedErrors,
        array $expected
    ): void {
        // ...
    }
}
