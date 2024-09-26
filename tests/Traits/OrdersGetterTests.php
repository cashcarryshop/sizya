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
    use CreateValidatorTrait;

    public function testGetOrders(): void
    {
        $getter = $this->createOrdersGetter();

        if ($getter) {
            $this->setUpBeforeTestGetOrders();

            $orders = $getter->getOrders();

            if (\count($orders) === 0) {
                $this->markTestIncomplete(
                    'No orders were found for '
                        . \get_class($getter)
                );
            }

            $this->assertContainsOnlyInstancesOf(OrderDTO::class, $orders);

            $validator = $this->createValidator();
            foreach ($orders as $order) {
                $violations = $validator->validate($order);
                $this->assertCount(0, $violations);
            }
        }
    }

    public function testGetOrdersByIds(): void
    {
        $getter = $this->createOrdersGetter();

        if ($getter) {
            foreach ($this->ordersIdsProvider() as $ids) {
                $orders = $getter->getOrdersByIds($ids);

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
                }
            }
        }
    }

    protected static function generateIds(array $ids): array
    {
        \shuffle($ids);

        return \array_map(
            static fn ($chunk) => [$chunk],
            \array_chunk($ids, 30)
        );
    }

    abstract protected function createOrdersGetter(): ?OrdersGetterInterface;

    abstract protected function ordersIdsProvider(): array;

    protected function setUpBeforeTestGetOrders(): void
    {
        // ...
    }
}
