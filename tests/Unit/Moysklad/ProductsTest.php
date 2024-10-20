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

use CashCarryShop\Sizya\DTO\ProductDTO;
use CashCarryShop\Sizya\Moysklad\Products;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithMoysklad;
use CashCarryShop\Sizya\Tests\Traits\ProductsGetterTests;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для получения товаров Moysklad.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(Products::class)]
class ProductsTest extends TestCase
{
    use InteractsWithMoysklad;
    use ProductsGetterTests {
        getProductsByIdsProvider as private _getProductsByIds;
    }

    protected function setUpBeforeTestGetProducts(array $expected): void
    {
        static::$handler->append(
            static::createMethodResponse('1.2/entity/assortment', [
                'expected' => $expected
            ])
        );
    }

    protected function getProductsByIdsProvider(): array
    {
        [$ids, $expected] = $this->_getProductsByIds();

        static::$handler->append(
            static::createMethodResponse('1.2/entity/assortment', [
                'expected' => \array_filter(
                    $expected,
                    static fn ($item) => $item instanceof ProductDTO
                )
            ])
        );

        return [$ids, $expected];
    }

    protected function setUpBeforeTestGetProductsByArticles(
        array $expectedProducts,
        array $expectedErrors,
        array $expected
    ): void {
        static::$handler->append(
            static::createMethodResponse('1.2/entity/assortment', [
                'expected' => $expectedProducts
            ]),
        );
    }

    protected function createProductsGetter(): Products
    {
        return new Products([
            'credentials'      => ['login', 'password'],
            'client'           => static::createHttpClient(static::$handler),
            'variantsIncludes' => true
        ]);
    }
}
