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

namespace Tests\Unit;

use CashCarryShop\Sizya\Moysklad\CustomerOrdersSource;
use Tests\Traits\OrdersGetterTests;
use Tests\Traits\OrdersGetterByAdditionalTests;
use Tests\Traits\InteractsWithMoysklad;
use Tests\Traits\GetFromDatasetTrait;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Covers;

/**
 * Тестирования класса для получения заказов МойСклад.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(CustomerOrdersSource::class)]
class CustomerOrdersSourceTest extends TestCase
{
    use InteractsWithMoysklad;
    use GetFromDatasetTrait;
    use OrdersGetterTests;
    use OrdersGetterByAdditionalTests {
        testGetOrdersByAdditional as private _traitTestGetOrdersByAdditional;
    }

    /**
     * Сущность
     *
     * @var ?CustomerOrdersSource
     */
    protected static ?CustomerOrdersSource $entity = null;

    /**
     * Настройка тестов МойСклад с перехватом
     * ошибки от api.
     *
     * @param array $credentials Данные авторизации
     *
     * @return void
     */
    protected static function setUpBeforeClassByMoysklad(array $credentials): void
    {
        if (is_null(static::getFromDataset(CustomerOrdersSource::class))) {
            static::markTestSkipped('Dataset for Moysklad customer orders source not found');
        }

        static::$entity = new CustomerOrdersSource(['credentials' => $credentials]);

        // Проверка что данные авторизации верные
        // и что есть права на писпользование
        // метода api.
        static::$entity->getOrders();
    }

    protected function createOrdersGetter(): ?CustomerOrdersSource
    {
        return static::$entity;
    }

    protected function createOrdersGetterByAdditional(): ?CustomerOrdersSource
    {
        return static::$entity;
    }

    #[DataProvider('ordersAdditionalProvider')]
    public function testGetOrdersByAdditional(string $entityId, array $values): void
    {
        $this->_traitTestGetOrdersByAdditional($entityId, $values);
    }

    #[Depends('testGetOrders')]
    public static function ordersIdsProvider(): array
    {
        return static::generateIds(
            static::getFromDataset(CustomerOrdersSource::class),
            \array_merge(
                \array_map(
                    fn () => self::_guidv4(),
                    \array_fill(0, 10, null)
                ),
                \array_map(
                    static fn () => 'validationErrorId',
                    \array_fill(0, 10, null)
                )
            )
        );
    }

    #[Depends('testGetOrders')]
    public static function ordersAdditionalProvider(): array
    {
        return static::generateAdditionals(
            static::getFromDataset(CustomerOrdersSource::class),
            \array_map(
                static fn () => 'invalidValue',
                \array_fill(0, 10, null)
            )
        );
    }

    private static function _guidv4($data = null) {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data = $data ?? \random_bytes(16);
        assert(\strlen($data) == 16);

        // Set version to 0100
        $data[6] = \chr(\ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = \chr(\ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return \vsprintf('%s%s-%s-%s-%s-%s%s%s', \str_split(\bin2hex($data), 4));
    }

    protected static function tearDownAfterClassByMoysklad(): void
    {
        static::$entity = null;
    }
}
