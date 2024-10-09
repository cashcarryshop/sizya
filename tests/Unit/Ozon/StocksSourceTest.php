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

namespace CashCarryShop\Sizya\Tests\Unit\Ozon;

use CashCarryShop\Sizya\Ozon\StocksSource;
use CashCarryShop\Sizya\Tests\Traits\StocksGetterTests;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithOzon;
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

    protected function setUpBeforeTestGetStocks(array $expected): void
    {
        foreach ($expected as $stock) {
            $stock->id          = (string) \random_int(100000000, 999999999);
            $stock->warehouseId = (string) \random_int(100000000, 999999999);
        }

        static::$handler->append(
            static::createMethodResponse('v2/product/list', [
                'expected' => $expected
            ]),
            static::createMethodResponse('v2/product/info/list', [
                'expected' => \array_map(
                    fn ($stock) => static::fakeProductDto([
                        'id'      => $stock->id,
                        'article' => $stock->article
                    ]),
                    $expected
                )
            ]),
            static::createMethodResponse('v1/product/info/stocks-by-warehouse/fbs', [
                'expected' => $expected
            ])
        );
    }

    protected function createStocksGetter(): StocksSource
    {
        return new StocksSource([
            'token'    => 'token',
            'clientId' => 123321,
            'limit'    => 100,
            'client'   => static::createHttpClient(static::$handler)
        ]);
    }
}
