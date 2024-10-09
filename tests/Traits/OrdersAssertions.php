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

use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * Трейт с тестами обновления заказов.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait OrdersAssertions
{
    use ByErrorAssertions;
    use AdditionalsAssertions;
    use PositionsAssertions;
    use AssertAndSplitByClassesTrait;

    /**
     * Сопоставить заказы.
     *
     * @param array $expected Ожидаемые
     * @param array $items    Полученные
     *
     * @return void
     */
    protected function assertOrders(array $expected, array $items): void
    {
        $this->assertSameSize($expected, $items);

        [
            $orders,
            $errors
        ] = $this->assertAndSplitByClasses(
            $items, [
                OrderDTO::class,
                ByErrorDTO::class
            ]
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();;

        $violations = $validator->validate($items, [new Assert\Valid]);
        $this->assertCount(0, $violations, (string) $violations);

        [
            $expectedOrders,
            $expectedErrors
        ] = $this->assertAndSplitByClasses(
            $expected, [
                OrderDTO::class,
                ByErrorDTO::class
            ]
        );


        $this->assertSameSize(
            $expectedOrders,
            $orders,
            'Orders must be have asme size with expected'
        );

        $this->assertSameSize(
            $expectedErrors,
            $errors,
            'Orders errors must be have asme size with expected'
        );

        \array_multisort(
            \array_column($expectedOrders, 'id'),
            SORT_STRING,
            $expectedOrders
        );

        \array_multisort(
            \array_column($orders, 'id'),
            SORT_STRING,
            $orders
        );

        \reset($orders);
        foreach ($expectedOrders as $expected) {
            $this->assertOrder($expected, \current($orders));
            \next($orders);
        }

        $this->assertByErrors($expectedErrors, $errors);
    }

    /**
     * Сопоставить ожидаемый товар и полученый.
     *
     * @param OrderDTO $expected Ожидаемый
     * @param OrderDTO $order    Полученый
     *
     * @return void
     */
    protected function assertOrder(OrderDTO $expected, OrderDTO $order): void
    {
        $this->assertEquals(
            $expected->id,
            $order->id,
            'Order is is invalid'
        );

        $this->assertEquals(
            $expected->created,
            $order->created,
            'Order created date is invalid'
        );

        $this->assertEquals(
            $expected->status,
            $order->status,
            'Order status is invalid'
        );

        $this->assertEquals(
            $expected->externalCode,
            $order->externalCode,
            'Order external code is invalid'
        );

        $this->assertEquals(
            $expected->shipmentDate,
            $order->shipmentDate,
            'Order shipment date is invalid'
        );

        $this->assertEquals(
            $expected->deliveringDate,
            $order->deliveringDate,
            'Order delivering date is invalid'
        );

        $this->assertEquals(
            $expected->description,
            $order->description,
            'Order description is invalid'
        );

        \array_multisort(
            \array_column($expected->additionals, 'id'),
            SORT_STRING,
            $expected->additionals
        );

        \array_multisort(
            \array_column($order->additionals, 'id'),
            SORT_STRING,
            $order->additionals
        );

        \reset($order->additionals);
        foreach ($expected as $expects) {
            $this->assertAdditional($expects, \current($order->additionals));
            \next($order->additionals);
        }

        \array_multisort(
            \array_column($expected->positions, 'id'),
            SORT_STRING,
            $expected->positions
        );

        \array_multisort(
            \array_column($order->positions, 'id'),
            SORT_STRING,
            $order->positions
        );

        \reset($order->positions);
        foreach ($expected->positions as $expects) {
            $this->assertPosition($expects, \current($order->positions));
            \next($order->positions);
        }
    }
}
