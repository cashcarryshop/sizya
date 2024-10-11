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

use CashCarryShop\Sizya\Moysklad\ProductsPricesSource;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithMoysklad;
use CashCarryShop\Sizya\Tests\Traits\ProductsPricesGetterTests;
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
#[CoversClass(ProductsPricesSource::class)]
class ProductsPricesSourceTest extends TestCase
{
    use InteractsWithMoysklad;
    use ProductsPricesGetterTests;

    protected function setUpBeforeTestGetProductsPrices(array $expected): void
    {
        static::$handler->append(
            static::createMethodResponse('1.2/entity/assortment', [
                'expected' => \array_map(
                    fn ($productPrices) => static::fakeProductDtoFromProductPrices(
                        $productPrices
                    ),
                    $expected
                )
            ])
        );
    }

    protected function setUpBeforeTestGetProductsPricesByIds(
        array $expectedProductsPrices,
        array $expectedErrors,
        array $expected
    ): void {
        static::$handler->append(
            static::createMethodResponse('1.2/entity/assortment', [
                'expected' => \array_map(
                    fn ($productPrices) => static::fakeProductDtoFromProductPrices(
                        $productPrices
                    ),
                    $expectedProductsPrices
                )
            ]),
        );
    }

    protected function setUpBeforeTestGetProductsPricesByArticles(
        array $expectedProductsPrices,
        array $expectedErrors,
        array $expected
    ): void {
        static::$handler->append(
            static::createMethodResponse('1.2/entity/assortment', [
                'expected' => \array_map(
                    fn ($productPrices) => static::fakeProductDtoFromProductPrices(
                        $productPrices
                    ),
                    $expectedProductsPrices
                )
            ]),
        );
    }

    protected function createProductsPricesGetter(): ProductsPricesSource
    {
        return new ProductsPricesSource([
            'credentials' => ['login', 'password'],
            'client'      => static::createHttpClient(static::$handler)
        ]);
    }
}
