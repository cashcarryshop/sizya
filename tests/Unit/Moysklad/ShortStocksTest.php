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

namespace Tests\Unit\Moysklad;

use CashCarryShop\Sizya\Moysklad\ShortStocks;
use Tests\Traits\StocksGetterTests;
use Tests\Traits\InteractsWithMoysklad;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для получения остатков МойСклад.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(ShortStocks::class)]
class ShortStocksTest extends TestCase
{
    use InteractsWithMoysklad;
    use StocksGetterTests;

    /**
     * Сущность.
     *
     * @var ?CustomerOrdersSource
     */
    protected static ?ShortStocks $entity = null;

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
        static::$entity = new ShortStocks(['credentials' => $credentials]);

        // Проверка что данные авторизации верные
        // и что есть права на писпользование
        // метода api.
        static::$entity->getStocks();
    }

    protected function createStocksGetter(): ?ShortStocks
    {
        return static::$entity;
    }

    protected static function tearDownAfterClassByMoysklad(): void
    {
        static::$entity = null;
    }
}
