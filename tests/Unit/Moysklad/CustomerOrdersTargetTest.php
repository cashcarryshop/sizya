<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Tests\Unit\Moysklad;

use CashCarryShop\Sizya\OrdersCreatorInterface;
use CashCarryShop\Sizya\OrdersUpdaterInterface;
use CashCarryShop\Sizya\Moysklad\CustomerOrdersTarget;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\OrderUpdateDTO;
use CashCarryShop\Sizya\DTO\OrderCreateDTO;
use CashCarryShop\Sizya\Tests\Traits\OrdersCreatorTests;
use CashCarryShop\Sizya\Tests\Traits\OrdersUpdaterTests;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithMoysklad;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тесты класса для создания/обновления заказов МойСклад.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(CustomerOrdersTarget::class)]
class CustomerOrdersTargetTest extends TestCase
{
    use OrdersCreatorTests;
    use OrdersUpdaterTests;
    use InteractsWithMoysklad;

    protected function setUpBeforeTestMassUpdateOrders(
        array $expected,
        array $forUpdate
    ): void {
        $this->_prepareOrders($expected);

        $responses = [];

        if ($products = $this->_getProducts($forUpdate)) {
            $responses[] = static::createMethodResponse(
                'get@1.2/entity/assortment', [
                    'expected' => $products
                ]
            );
            $responses[] = static::createMethodResponse(
                'get@1.2/entity/assortment', [
                    'expected' => []
                ]
            );
        }

        $responses[] = static::createMethodResponse(
            'post@1.2/entity/customerorder', [
                'expected' => $expected
            ]
        );

        static::$handler->append(...$responses);
    }

    protected function setUpBeforeTestMassCreateOrders(
        array $expected,
        array $forCreate
    ): void {
        $this->_prepareOrders($expected);

        $responses = [];

        if ($products = $this->_getProducts($forCreate)) {
            $responses[] = static::createMethodResponse(
                'get@1.2/entity/assortment', [
                    'expected' => $products
                ]
            );
            $responses[] = static::createMethodResponse(
                'get@1.2/entity/assortment', [
                    'expected' => []
                ]
            );
        }

        $responses[] = static::createMethodResponse(
            'post@1.2/entity/customerorder', [
                'expected' => $expected
            ]
        );

        static::$handler->append(...$responses);
    }

    protected function setUpBeforeTestUpdateOrder(
        OrderDTO       $expected,
        OrderUpdateDTO $forUpdate
    ): void {
        $this->_prepareOrders([$expected]);

        $responses = [];

        if ($products = $this->_getProducts([$forUpdate])) {
            $responses[] = static::createMethodResponse(
                'get@1.2/entity/assortment', [
                    'expected' => $products
                ]
            );
            $responses[] = static::createMethodResponse(
                'get@1.2/entity/assortment', [
                    'expected' => []
                ]
            );
        }

        $responses[] = static::createMethodResponse(
            'post@1.2/entity/customerorder', [
                'expected' => [$expected]
            ]
        );

        static::$handler->append(...$responses);
    }

    protected function setUpBeforeTestCreateOrder(
        OrderDTO       $expected,
        OrderCreateDTO $forCreate
    ): void {
        $this->_prepareOrders([$expected]);

        $responses = [];

        if ($products = $this->_getProducts([$forCreate])) {
            $responses[] = static::createMethodResponse(
                'get@1.2/entity/assortment', [
                    'expected' => $products
                ]
            );
            $responses[] = static::createMethodResponse(
                'get@1.2/entity/assortment', [
                    'expected' => []
                ]
            );
        }

        $responses[] = static::createMethodResponse(
            'post@1.2/entity/customerorder', [
                'expected' => [$expected]
            ]
        );

        static::$handler->append(...$responses);
    }

    protected function createOrdersUpdater(): OrdersUpdaterInterface
    {
        return new CustomerOrdersTarget([
            'credentials'  => ['login', 'password'],
            'organization' => static::guidv4(),
            'agent'        => static::guidv4(),
            'client'       => static::createHttpClient(static::$handler),
            'limit'        => 100
        ]);
    }

    protected function createOrdersCreator(): ?OrdersCreatorInterface
    {
        return new CustomerOrdersTarget([
            'credentials'  => ['login', 'password'],
            'organization' => static::guidv4(),
            'agent'        => static::guidv4(),
            'client'       => static::createHttpClient(static::$handler),
            'limit'        => 100
        ]);
    }

    /**
     * Получить товары из заказов
     *
     * @param array $orders Заказы
     *
     * @return array
     */
    private function _getProducts(array $orders): array
    {
        $products = [];
        foreach ($orders as $order) {
            if (\property_exists($order, 'positions')) {
                foreach ($order->positions as $position) {
                    if (isset($products[$position->article])) {
                        continue;
                    }

                    if ($position->productId === null) {
                        $products[$position->article] = static::fakeProductDto([
                            'id'      => static::guidv4(),
                            'article' => $position->article,
                            'type'    => $position->type
                        ]);
                    }
                }
            }
        }

        return \array_values($products);
    }

    /**
     * Обработать заказы.
     *
     * @param OrderDTO $orders Заказы
     *
     * @return void
     */
    private function _prepareOrders(array $orders): void
    {
        foreach ($orders as $order) {
            $order->deliveringDate = null;

            foreach ($order->additionals as $additional) {
                $additional->entityId = $additional->id;
            }

            if (\property_exists($order, 'positions')) {
                foreach ($order->positions as $position) {
                    $position->currency = null;
                }
            }
        }
    }
}
