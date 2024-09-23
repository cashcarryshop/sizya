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

use CashCarryShop\Sizya\Ozon\StocksSource;
use Tests\Traits\StocksGetterTests;
use Tests\Traits\InteractsWithOzon;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для получения остатков Ozon.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(StocksSource::class)]
class StocksSourceTest extends TestCase
{
    use InteractsWithOzon;
    use StocksGetterTests;

    /**
     * Сущность.
     *
     * @var ?CustomerOrdersSource
     */
    protected static ?StocksSource $entity = null;

    /**
     * Настройка тестов Ozon с перехватом
     * ошибки от api.
     *
     * @param array $credentials Данные авторизации
     *
     * @return void
     */
    protected static function setUpBeforeClassByOzon(array $credentials): void
    {
        static::$entity = new StocksSource($credentials);

        // Проверка что данные авторизации верные
        // и что есть права на писпользование
        // метода api.
        static::$entity->getStocks();
    }

    protected function createStocksGetter(): ?ShortStocks
    {
        return static::$entity;
    }

    protected static function tearDownAfterClassByOzon(): void
    {
        static::$entity = null;
    }
}
