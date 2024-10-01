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

use CashCarryShop\Sizya\OrdersCreatorInterface;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\OrdersUpdateDTO;
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
 * @see OrdersCreatorInterface
 */
trait OrdersCreatorTests
{
    use UpdateValidatorTrait;

    public function testMassUpdateOrders(): void
    {
        $creator = $this->createOrdersCreator();

        if ($creator) {
            $forUpdate = $this->ordersUpdateProvider();
            $updated   = $creator->massUpdateOrders($forUpdate);

            $this->assertSameSize($forUpdate, $updated);

            $validator = $this->createValidator();
            foreach ($updated as $order) {
                $this->assertThat(
                    $order,
                    $this->logicalOr(
                        $this->isInstanceOf(OrderDTO::class),
                        $this->isInstanceOf(ByErrorDTO::class)
                    )
                );

                $violations = $validator->validate($order);
                $this->assertCount(0, $violations, (string) $violations);
            }

            return;
        }

        $this->markTestIncomplete('Orders creator is null');
    }

    public function testUpdateOrder(): void
    {
        $creator = $this->createOrdersCreator();

        if ($creator) {
            $forUpdate = $this->orderUpdateProvider();
            $updated   = $creator->massUpdateOrders();

            $validator = $this->createValidator();
            $this->assertThat(
                $updated,
                $this->logicalOr(
                    $this->isInstanceOf(OrderDTO::class),
                    $this->isInstanceOf(ByErrorDTO::class)
                )
            );

            $violations = $validator->validate($updated);
            $this->assertCount(0, $violations, (string) $violations);

            return;
        }

        $this->markTestIncomplete('Orders creator is null');
    }

    abstract protected function createOrdersCreator(): ?OrdersCreatorInterface;

    abstract protected function ordersUpdateProvider(): array;

    abstract protected function orderUpdateProvider(): OrderUpdateDTO;
}
