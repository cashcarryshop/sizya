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

use CashCarryShop\Sizya\DTO\ProductDTO;
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
trait ProductsAssertions
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
    protected function assertProducts(array $expected, array $items): void
    {
        $this->assertSameSize($expected, $items);

        [
            $products,
            $errors
        ] = $this->assertAndSplitByClasses(
            $items, [
                ProductDTO::class,
                ByErrorDTO::class
            ]
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();;

        $violations = $validator->validate($items, [new Assert\Valid]);
        $this->assertCount(0, $violations, (string) $violations);

        [
            $expectedProducts,
            $expectedErrors
        ] = $this->assertAndSplitByClasses(
            $expected, [
                ProductDTO::class,
                ByErrorDTO::class
            ]
        );

        $this->assertSameSize(
            $expectedProducts,
            $products,
            'Products must be have asme size with expected'
        );

        $this->assertSameSize(
            $expectedErrors,
            $errors,
            'Products errors must be have asme size with expected'
        );

        \array_multisort(
            \array_column($expectedProducts, 'id'),
            SORT_STRING,
            $expectedProducts
        );

        \array_multisort(
            \array_column($products, 'id'),
            SORT_STRING,
            $products
        );

        \reset($products);
        foreach ($expectedProducts as $expected) {
            $this->assertProduct($expected, \current($products));
            \next($products);
        }

        $this->assertByErrors($expectedErrors, $errors);
    }

    /**
     * Сопоставить ожидаемый товар и полученый.
     *
     * @param ProductDTO $expected Ожидаемый
     * @param ProductDTO $product  Полученый
     *
     * @return void
     */
    protected function assertProduct(ProductDTO $expected, ProductDTO $product): void
    {
        $this->assertEquals(
            $expected->id,
            $product->id,
            'Product id is invalid'
        );

        $this->assertEquals(
            $expected->article,
            $product->article,
            'Article is invalid'
        );

        $this->assertEquals(
            $expected->created,
            $product->created,
            'Product created date is invalid'
        );

        \array_multisort(
            \array_column($expected->prices, 'id'),
            SORT_STRING,
            $expected->prices
        );

        \array_multisort(
            \array_column($product->prices, 'id'),
            SORT_STRING,
            $product->prices
        );

        \reset($expected->prices);
        foreach ($product->prices as $price) {
            $expectedPrice = \current($expected->prices);

            $this->assertEquals(
                $expectedPrice->id,
                $price->id,
                'Price id is invalid'
            );

            $this->assertEquals(
                $expectedPrice->name,
                $price->name,
                'Price name is invalid'
            );

            $this->assertEquals(
                $expectedPrice->value,
                $price->value,
                'Price value is invalid'
            );

            \next($expected->prices);
        }
    }
}
