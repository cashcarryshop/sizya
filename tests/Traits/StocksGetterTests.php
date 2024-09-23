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

namespace Tests\Traits;

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
    use CreateValidatorTrait;

    public function testGetStocks(): void
    {
        $getter = $this->createStocksGetter();

        if ($getter) {
            $stocks = $getter->getStocks();

            $this->assertContainsOnlyInstancesOf(StockDTO::class, $stocks);

            $validator = $this->createValidator();
            foreach ($stocks as $stock) {
                $violations = $validator->validate($stock);
                $this->assertCount(0, $violations);
            }
        }
    }

    abstract protected function createStocksGetter(): ?StocksGetterInterface;
}
