<?php
declare(strict_types=1);
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

namespace Tests\Traits;

use CashCarryShop\Sizya\OrdersGetterInterface;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Трейт с тестами получения заказов.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see OrdersGetterByAdditionalInterface
 */
trait OrdersGetterTests
{
    use CreateValidatorTrait;

    public function testGetOrders(): void
    {
        $ordersGetter = $this->createOrdersGetter();

        if ($ordersGetter) {
            $orders = $ordersGetter->getOrders();

            if (\count($orders) === 0) {
                $this->markTestIncomplete(
                    'No orders were found for '
                        . \get_class($ordersGetter)
                );
                return;
            }

            $this->assertContainsOnlyInstancesOf(OrderDTO::class, $orders);

            $validator = $this->createValidator();
            foreach ($orders as $order) {
                $this->assertInstanceOf(OrderDTO::class, $order);
                $violations = $validator->validate($order);
                $this->assertCount(0, $violations);
            }
        }
    }

    #[Depends('testGetOrders')]
    #[DataProvider('ordersIdsProvider')]
    public function testGetOrdersByIds(array $ids): void
    {
        $ordersGetter = $this->createOrdersGetter();

        if ($ordersGetter) {
            $orders = $ordersGetter->getOrdersByIds($ids);

            $this->assertSameSize($ids, $orders);

            $validator = $this->createValidator();
            foreach ($orders as $order) {
                $this->assertThat(
                    $order,
                    $this->logicalOr(
                        $this->isInstanceOf(OrderDTO::class),
                        $this->isInstanceOf(ByErrorDTO::class)
                    )
                );

                $violations = $validator->validate($order);
                $this->assertCount(0, $violations);
                break;
            }
        }
    }

    protected static function generateIds(array $orders, array $invalidIds): array
    {
        $ids = \array_merge(
            \array_map(
                static fn ($order) => $order->id,
                $orders
            ),
            $invalidIds
        );

        \shuffle($ids);

        return \array_map(
            static fn ($chunk) => [$chunk],
            \array_chunk($ids, 30)
        );
    }

    abstract protected function createOrdersGetter(): ?OrdersGetterInterface;

    abstract public static function ordersIdsProvider(): array;
}
