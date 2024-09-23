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

use CashCarryShop\Sizya\StocksUpdaterInterface;
use CashCarryShop\Sizya\DTO\StockDTO;
use CashCarryShop\Sizya\DTO\UpdateStockDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Трейт с тестами для получения остатков.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see StocksUpdaterInterface
 */
trait StocksUpdaterTrait
{
    use CreateValidatorTrait;

    #[DataProvider('updateStocksProvider')]
    public function testUpdateStocks(array $updateStocks): void
    {
        $updater = $this->createStocksUpdater();

        if ($updater) {
            $results = $updater->updateStocks($updateStocks);

            $this->assertSameSize($updateStocks, $results);

            foreach ($results as $result) {
                $this->assertThat(
                    $result,
                    $this->logicalOr(
                        $this->isInstanceOf(StockDTO::class),
                        $this->isInstanceOf(ByErrorDTO::class)
                    )
                );
            }

            $validator = $this->createValidator();
            foreach ($stocks as $stock) {
                $violations = $validator->validate($stock);
                $this->assertCount(0, $violations);
            }

            $this->resetStocks($updater, $updateStocks);
        }
    }

    abstract protected function createStocksUpdater(): ?StocksUpdaterInterface;

    abstract public static function updateStocksProvider(): array;

    abstract protected function resetStocks(StocksUpdaterInterface $updater, array $updateStocks);
}
