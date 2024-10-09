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

use CashCarryShop\Sizya\StocksGetterInterface;
use CashCarryShop\Sizya\DTO\StockDTO;

/**
 * Трейт с тестами для получения остатков.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see StocksGetterInterface
 */
trait StocksGetterTests
{
    use InteractsWithFakeData;
    use StocksAssertions;

    public function testGetStocks(): void
    {
        $getter = $this->createStocksGetter();

        $expected = \array_map(
            fn () => StockDTO::fromArray([
                'id'          => static::guidv4(),
                'article'     => static::fakeArticle(),
                'warehouseId' => static::guidv4(),
                'quantity'    => \random_int(0, 25)
            ]),
            \array_fill(0, 10, null)
        );

        $this->setUpBeforeTestGetStocks($expected);

        $this->assertStocks($expected, $getter->getStocks());
    }

    abstract protected function createStocksGetter(): StocksGetterInterface;

    protected function setUpBeforeTestGetStocks(array $expected): void
    {
        // ...
    }
}
