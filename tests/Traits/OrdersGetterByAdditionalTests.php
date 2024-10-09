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

use CashCarryShop\Sizya\OrdersGetterByAdditionalInterface;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\DTO\AdditionalDTO;
use CashCarryShop\Sizya\DTO\PositionDTO;

/**
 * Трейт с тестами получения заказов по доп. полям.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see OrdersGetterByAdditionalInterface
 */
trait OrdersGetterByAdditionalTests
{
    use InteractsWithFakeData;
    use OrdersAssertions;

    public function testGetOrdersByAdditional(): void
    {
        [
            'values'  => $values,
            'valid'   => $valid,
            'invalid' => $invalid
        ] = static::generateFakeData();

        $entityId = static::guidv4();

        $getter = $this->createOrdersGetterByAdditional();

        $expectedOrders = [];
        $expectedErrors = [];
        $expected       = \array_map(
            function ($value) use (
                $invalid,
                $entityId,
                &$expectedOrders,
                &$expectedErrors
            ) {
                if (\in_array($value, $invalid)) {
                    return $expectedErrors[] = ByErrorDTO::fromArray([
                        'type'  => ByErrorDTO::NOT_FOUND,
                        'value' => $value
                    ]);
                }

                $order = static::fakeOrderDto();
                $order->additionals = [
                    AdditionalDTO::fromArray([
                        'id'       => static::guidv4(),
                        'entityId' => static::guidv4(),
                        'name'     => static::fakeArticle(),
                        'value'    => static::fakeString()
                    ]),
                    AdditionalDTO::fromArray([
                        'id'       => static::guidv4(),
                        'entityId' => $entityId,
                        'name'     => \sha1($entityId),
                        'value'    => $value
                    ])
                ];

                return $expectedOrders[] = $order;
            },
            $values
        );

        $this->setUpBeforeTestGetOrdersByAdditional(
            $expectedOrders,
            $expectedErrors,
            $expected
        );

        $this->assertOrders(
            $expected,
            $getter->getOrdersByAdditional($entityId, $values)
        );
    }

    abstract protected function createOrdersGetterByAdditional(): ?OrdersGetterByAdditionalInterface;

    protected function setUpBeforeTestGetOrdersByAdditional(
        array $expectedOrders,
        array $expectedErrors,
        array $expected
    ): void {
        // ...
    }
}
