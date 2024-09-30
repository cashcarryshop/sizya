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

use CashCarryShop\Sizya\Moysklad\ShortStocks;
use CashCarryShop\Sizya\Tests\Traits\StocksGetterTests;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithMoysklad;
use CashCarryShop\Sizya\Tests\TestCase;
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


    public static function setUpBeforeClass(): void
    {
        static::$entity = new ShortStocks([
            'credentials' => ['login', 'password'],
            'client'      => static::createHttpClient(static::$handler),
            'stockType'   => 'quantity'
        ]);
    }

    protected function createStocksGetter(): ?ShortStocks
    {
        return static::$entity;
    }

    protected function setUpBeforeTestGetStocks(): void
    {
        static::$handler->append(
            static::createMethodResponse('1.2/report/stock/bystore/current'),
            static::createMethodResponse('1.2/entity/assortment'),
            static::createMethodResponse('1.2/entity/assortment')
        );
    }

    public static function tearDownAfterClass(): void
    {
        static::$entity = null;
    }
}
