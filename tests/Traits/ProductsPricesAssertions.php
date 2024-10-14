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

use CashCarryShop\Sizya\DTO\ProductPricesDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * Трейт с методами для проверки товаров.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait ProductsPricesAssertions
{
    use ByErrorAssertions;
    use PricesAssertions;
    use AssertAndSplitByClassesTrait;

    /**
     * Сопоставить товары.
     *
     * @param array $expected Ожидаемые
     * @param array $items    Полученные
     *
     * @return void
     */
    protected function assertProductsPrices(array $expected, array $items): void
    {
        $this->assertSameSize(
            $expected,
            $items,
            'Products prices common size must be equals'
        );

        [
            $productsPrices,
            $errors
        ] = $this->assertAndSplitByClasses(
            $items, [
                ProductPricesDTO::class,
                ByErrorDTO::class
            ]
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();;

        $violations = $validator->validate($items, [new Assert\Valid]);
        $this->assertCount(0, $violations, (string) $violations);

        [
            $expectedProductsPrices,
            $expectedErrors
        ] = $this->assertAndSplitByClasses(
            $expected, [
                ProductPricesDTO::class,
                ByErrorDTO::class
            ]
        );

        $this->assertSameSize(
            $expectedProductsPrices,
            $productsPrices,
            'Products prices must be have same size with expected'
        );

        $this->assertSameSize(
            $expectedErrors,
            $errors,
            'Products prices errors must be have same size with expected'
        );

        \array_multisort(
            \array_column($expectedProductsPrices, 'id'),
            SORT_STRING,
            $expectedProductsPrices
        );

        \array_multisort(
            \array_column($productsPrices, 'id'),
            SORT_STRING,
            $productsPrices
        );

        \reset($productsPrices);
        foreach ($expectedProductsPrices as $expected) {
            $this->assertProductPrices($expected, \current($productsPrices));
            \next($productsPrices);
        }

        $this->assertByErrors($expectedErrors, $errors);
    }

    /**
     * Сопоставить ожидаемый товар и полученый.
     *
     * @param ProductPricesDTO $expected       Ожидаемый
     * @param ProductPricesDTO $productPrices  Полученый
     *
     * @return void
     */
    protected function assertProductPrices(
        ProductPricesDTO $expected,
        ProductPricesDTO $productPrices
    ): void
    {
        $this->assertEquals(
            $expected->id,
            $productPrices->id,
            'Product id is invalid'
        );

        $this->assertEquals(
            $expected->article,
            $productPrices->article,
            'Product article is invalid'
        );

        $this->assertPrices($expected->prices, $productPrices->prices);
    }
}
