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
use CashCarryShop\Sizya\DTO\OrderCreateDTO;
use CashCarryShop\Sizya\DTO\AdditionalCreateDTO;
use CashCarryShop\Sizya\DTO\PositionCreateDTO;
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
    use InteractsWithFakeData;
    use OrdersAssertions;

    public function testMassCreateOrders(): void
    {
        $creator = $this->createOrdersCreator();

        $expected = \array_map(
            fn () => static::fakeOrderDto(),
            \array_fill(0, 10, null)
        );

        $forCreate = \array_map(
            fn ($order) => OrderCreateDTO::fromArray([
                'created'        => $order->created,
                'status'         => $order->status,
                'shipmentDate'   => $order->shipmentDate,
                'deliveringDate' => $order->deliveringDate,
                'description'    => $order->description,
                'additionals'    => \array_map(
                    fn ($additional) => AdditionalCreateDTO::fromArray([
                        'entityId' => $additional->entityId,
                        'value'    => $additional->value
                    ]),
                    $order->additionals
                ),
                'positions' => \array_map(
                    fn ($position) => PositionCreateDTO::fromArray([
                        'productId' => \in_array($rand = \random_int(1, 3), [1, 2])
                            ? $position->productId
                            : null,
                        'article'  => $rand === 2 ? $position->article : null,
                        'type'     => $rand === 3 ? 'product' : 'variant',
                        'quantity' => $position->quantity,
                        'reserve'  => $position->reserve,
                        'price'    => $position->price,
                        'discount' => $position->discount,
                        'currency' => $position->currency
                    ]),
                    $order->positions
                )
            ]),
            $expected
        );

        $this->setUpBeforeTestMassCreateOrders($expected, $forCreate);

        $this->assertOrders($expected, $creator->massCreateOrders($forCreate));
    }

    public function testCreateOrder(): void
    {
        $creator = $this->createOrdersCreator();

        $expected  = static::fakeOrderDto();
        $forCreate = OrderCreateDTO::fromArray([
            'created'        => $expected->created,
            'status'         => $expected->status,
            'shipmentDate'   => $expected->shipmentDate,
            'deliveringDate' => $expected->deliveringDate,
            'description'    => $expected->description,
            'additionals'    => \array_map(
                fn ($additional) => AdditionalCreateDTO::fromArray([
                    'entityId' => $additional->entityId,
                    'value'    => $additional->value
                ]),
                $expected->additionals
            ),
            'positions' => \array_map(
                fn ($position) => PositionCreateDTO::fromArray([
                    'productId' => \in_array($rand = \random_int(1, 3), [1, 2])
                        ? $position->productId
                        : null,
                    'article'  => $rand === 2 ? $position->article : null,
                    'type'     => $rand === 3 ? 'product' : 'variant',
                    'quantity' => $position->quantity,
                    'reserve'  => $position->reserve,
                    'price'    => $position->price,
                    'discount' => $position->discount,
                    'currency' => $position->currency
                ]),
                $expected->positions
            )
        ]);

        $this->setUpBeforeTestCreateOrder($expected, $forCreate);

        $this->assertOrders([$expected], [$creator->createOrder($forCreate)]);
    }

    abstract protected function createOrdersCreator(): ?OrdersCreatorInterface;

    protected function setUpBeforeTestMassCreateOrders(
        array $expected,
        array $forCreate
    ): void {
        // ...
    }

    protected function setUpBeforeTestCreateOrder(
        OrderDTO       $expected,
        OrderCreateDTO $forCreate
    ): void {
        // ...
    }
}
