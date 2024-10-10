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

use CashCarryShop\Sizya\DTO\StockDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * Трейт с методами для проверки остатков.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait StocksAssertions
{
    use ByErrorAssertions;
    use AssertAndSplitByClassesTrait;

    /**
     * Сопоставить товары.
     *
     * @param array $expected Ожидаемые
     * @param array $items    Полученные
     *
     * @return void
     */
    protected function assertStocks(
        array $expected,
        array $items
    ): void {
        $this->assertSameSize(
            $expected,
            $items,
            'Stocks common size must be equals'
        );

        [
            $stocks,
            $errors
        ] = $this->assertAndSplitByClasses(
            $items, [
                StockDTO::class,
                ByErrorDTO::class
            ]
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();;

        $violations = $validator->validate($items, [new Assert\Valid]);
        $this->assertCount(0, $violations, (string) $violations);

        [
            $expectedStocks,
            $expectedErrors
        ] = $this->assertAndSplitByClasses(
            $expected, [
                StockDTO::class,
                ByErrorDTO::class
            ]
        );

        $this->assertSameSize(
            $expectedStocks,
            $stocks,
            'Stocks must be have same size with expected'
        );

        $this->assertSameSize(
            $expectedErrors,
            $errors,
            'Stocks errors must be have same size with expected'
        );

        \array_multisort(
            \array_column($expectedStocks, 'id'),
            SORT_STRING,
            $expectedStocks
        );

        \array_multisort(
            \array_column($stocks, 'id'),
            SORT_STRING,
            $stocks
        );

        \reset($stocks);
        foreach ($expectedStocks as $expected) {
            $this->assertStock($expected, \current($stocks));
            \next($stocks);
        }

        $this->assertByErrors($expectedErrors, $errors);
    }

    /**
     * Сопоставить ожидаемый остаток и полученый.
     *
     * @param StockDTO $expected Ожидаемый
     * @param StockDTO $stock    Полученый
     *
     * @return void
     */
    protected function assertStock(StockDTO $expected, StockDTO $stock): void
    {
        $this->assertEquals(
            $expected->id,
            $stock->id,
            'Stock product id is invalid'
        );

        $this->assertEquals(
            $expected->article,
            $stock->article,
            'Stock product article is invalid'
        );

        $this->assertEquals(
            $expected->warehouseId,
            $stock->warehouseId,
            'Stock warehouse id is invalid'
        );

        $this->assertEquals(
            $expected->quantity,
            $stock->quantity,
            'Stock quantity is invalid'
        );
    }
}
