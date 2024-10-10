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

    protected function createStocksGetter(): ShortStocks
    {
        return new ShortStocks([
            'credentials' => ['login', 'password'],
            'client'      => static::createHttpClient(static::$handler),
            'stockType'   => 'quantity'
        ]);
    }

    protected function setUpBeforeTestGetStocks(array $expected): void
    {
        $products = [];
        foreach ($expected as $stock) {
            if (isset($products[$stock->id])) {
                continue;
            }

            $products[$stock->id] = static::fakeProductDto([
                'id'       => $stock->id,
                'article'  => $stock->article
            ]);
        }

        static::$handler->append(
            static::createMethodResponse('1.2/report/stock/bystore/current', [
                'expected' => $expected
            ]),
            static::createMethodResponse('1.2/entity/assortment', [
                'expected' => $products
            ]),
        );
    }
}
