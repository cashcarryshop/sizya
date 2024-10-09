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

use CashCarryShop\Sizya\Ozon\StocksTarget;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithOzon;
use CashCarryShop\Sizya\Tests\Traits\StocksUpdaterTests;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для обновления остатков Ozon.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(StocksTarget::class)]
class StocksTargetTest extends TestCase
{
    use InteractsWithOzon;
    use StocksUpdaterTests;

    protected function createStocksUpdater(): StocksTarget
    {
        return new StocksTarget([
            'token'    => 'token',
            'clientId' => 123321,
            'limit'    => 100,
            'client'   => static::createHttpClient(static::$handler)
        ]);
    }

    protected function setUpBeforeTestUpdateStocks(
        array $expected,
        array $forUpdate
    ): void {
        foreach ($expected as $stock) {
            $id          = (string) \random_int(100000000, 999999999);
            $warehouseId = (string) \random_int(100000000, 999999999);

            foreach ($forUpdate as $updateStock) {
                if ($stock->id === $updateStock->id) {
                    $updateStock->id = $id;
                }

                if ($stock->warehouseId === $updateStock->warehouseId) {
                    $updateStock->article = $warehouseId;
                }
            }

            $stock->id          = $id;
            $stock->warehouseId = $warehouseId;
        }

        static::$handler->append(
            static::createMethodResponse('v2/products/stocks', [
                'expected' => $expected
            ])
        );
    }
}
