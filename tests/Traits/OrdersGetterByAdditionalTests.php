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
    use CreateValidatorTrait;

    public function testGetOrdersByAdditional(): void
    {
        $getter = $this->createOrdersGetterByAdditional();

        if ($getter) {
            foreach ($this->ordersAdditionalProvider() as [$entityId, $values]) {
                $orders = $getter->getOrdersByAdditional($entityId, $values);

                $this->assertGreaterThanOrEqual(
                    \count($values),
                    \count($orders),
                    'The number of orders must be equal to or greater than passed values'
                );

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

            return;
        }

        $this->markTestIncomplete('Orders additional getter is null');
    }

    abstract protected function createOrdersGetterByAdditional(): ?OrdersGetterByAdditionalInterface;

    abstract protected function ordersAdditionalProvider(): array;
}
