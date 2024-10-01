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
use CashCarryShop\Sizya\DTO\OrdersCreateDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;

/**
 * Трейт с тестами создания заказов.
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
    use CreateValidatorTrait;

    public function testMassCreateOrders(): void
    {
        $creator = $this->createOrdersCreator();

        if ($creator) {
            foreach ($this->ordersCreateProvider() as $forCreate) {
                $updated = $creator->massCreateOrders($forCreate);

                $this->assertSameSize($forCreate, $updated);

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
            }

            return;
        }

        $this->markTestIncomplete('Orders creator is null');
    }

    public function testCreateOrder(): void
    {
        $creator = $this->createOrdersCreator();

        if ($creator) {
            $forCreate = $this->orderCreateProvider();
            $updated   = $creator->massCreateOrders();

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

    abstract protected function ordersCreateProvider(): array;

    abstract protected function orderCreateProvider(): OrderCreateDTO;
}
