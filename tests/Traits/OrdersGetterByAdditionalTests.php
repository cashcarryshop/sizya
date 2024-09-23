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

namespace Tests\Traits;

use CashCarryShop\Sizya\OrdersGetterByAdditionalInterface;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use PHPUnit\Framework\Attributes\DataProvider;

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

    #[DataProvider('ordersAdditionalProvider')]
    public function testGetOrdersByAdditional(string $entityId, array $values): void
    {
        $ordersGetter = $this->createOrdersGetterByAdditional();

        if ($ordersGetter) {
            $orders = $ordersGetter->getOrdersByAdditional($entityId, $values);

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
    }

    /**
     * Сгенерировать данные для testGetOrdersbyAdditional
     *
     * @return array
     */
    protected static function generateAdditionals(array $orders, array $invalidValues): array
    {
        $data = [];
        foreach ($orders as $order) {
            if (\count($order->additionals)) {
                foreach ($order->additionals as $additional) {
                    if (isset($data[$additional->entityId])) {
                        $data[$additional->entityId][] = $additional->value;
                        continue;
                    }

                    $data[$additional->entityId] = [$additional->value];
                }
            }
        }

        while ($value = array_pop($invalidValues)) {
            $data[\array_rand($data)][] = $value;
        }

        return \array_values(
            \array_map(
                static function ($entityId, $values) {
                    shuffle($values);
                    return [$entityId, $values];
                },
                \array_keys($data), $data
            )
        );
    }

    abstract protected function createOrdersGetterByAdditional(): ?OrdersGetterByAdditionalInterface;

    abstract public static function ordersAdditionalProvider(): array;
}
