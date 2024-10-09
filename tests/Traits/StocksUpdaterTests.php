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

use CashCarryShop\Sizya\StocksUpdaterInterface;
use CashCarryShop\Sizya\DTO\StockDTO;
use CashCarryShop\Sizya\DTO\StockUpdateDTO;

/**
 * Трейт с тестами для обновления остатков.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see StocksUpdaterInterface
 */
trait StocksUpdaterTests
{
    use InteractsWithFakeData;
    use StocksAssertions;

    public function testUpdateStocks(): void
    {
        $updater = $this->createStocksUpdater();

        $expected = \array_map(
            fn () => StockDTO::fromArray([
                'id'          => static::guidv4(),
                'article'     => static::fakeArticle(),
                'warehouseId' => static::guidv4(),
                'quantity'    => \random_int(0, 25)
            ]),
            \array_fill(0, 10, null)
        );

        $forUpdate = \array_map(
            fn ($stock) => StockUpdateDTO::fromArray([
                'id'      => $id = \random_int(0, 3) === 3 ? $stock->id : null,
                'article' => $id
                    ? (\random_int(0, 2) === 2 ? $stock->article : null)
                    : $stock->article,
                'warehouseId' => $stock->warehouseId,
                'quantity'    => $stock->quantity
            ]),
            $expected
        );

        $this->setUpBeforeTestUpdateStocks($expected, $forUpdate);

        $this->assertStocks($expected, $updater->updateStocks($forUpdate));
    }

    abstract protected function createStocksUpdater(): StocksUpdaterInterface;

    protected function setUpBeforeTestUpdateStocks(
        array $expected,
        array $forUpdate
    ): void {
        // ...
    }
}
