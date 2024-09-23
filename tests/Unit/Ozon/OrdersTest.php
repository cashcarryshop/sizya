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

namespace Tests\Unit\Ozon;

use CashCarryShop\Sizya\Ozon\Orders;
use Tests\Traits\InteractsWithOzon;
use Tests\Traits\OrdersGetterTests;
use Tests\Traits\GetFromDatasetTrait;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для получения заказов Ozon..
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(Orders::class)]
class OrdersTest extends TestCase
{
    use InteractsWithOzon;
    use GetFromDatasetTrait;
    use OrdersGetterTests;

    /**
     * Используемыая сущность.
     *
     * @var ?Orders
     */
    protected static ?Orders $entity = null;

    protected static function setUpBeforeClassByOzon(array $credentials): void
    {
        if (is_null(static::getFromDataset(Orders::class))) {
            static::markTestSkipped('Dataset for Ozon orders not found');
        }

        static::$entity = new Orders($credentials);

        // Проверка что данные авторизации верные
        // и что есть права на писпользование
        // метода api.
        static::$entity->getOrders();
    }

    protected function createOrdersGetter(): ?Orders
    {
        return static::$entity;
    }

    public static function ordersIdsProvider(): array
    {
        return static::generateIds(
            static::getFromDataset(Orders::class),
            \array_map(
                static fn () => 'invalidId',
                array_fill(0, 10, null)
            )
        );
    }

    protected static function tearDownAfterClassByOzon(): void
    {
        static::$entity = null;
    }
}
