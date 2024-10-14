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

use CashCarryShop\Sizya\Ozon\ProductsPricesSource;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithOzon;
use CashCarryShop\Sizya\Tests\Traits\ProductsPricesGetterTests;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для получения товаров Ozon.
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
    use InteractsWithOzon;
    use ProductsPricesGetterTests;

    protected function setUpBeforeTestGetProductsPrices(array $expected): void
    {
        $this->_prepareProducts($expected);

        static::$handler->append(
            static::createMethodResponse(
                'v4/product/info/prices', [
                    'expected' => $expected
                ]
            )
        );
    }

    protected function setUpBeforeTestGetProductsPricesByIds(
        array $expectedProductsPrices,
        array $expectedErrors,
        array $expected
    ): void {
        $this->_prepareProducts($expectedProductsPrices);

        static::$handler->append(
            static::createMethodResponse(
                'v4/product/info/prices', [
                    'expected' => $expectedProductsPrices
                ]
            )
        );
    }

    protected function setUpBeforeTestGetProductsPricesByArticles(
        array $expectedProductsPrices,
        array $expectedErrors,
        array $expected
    ): void {
        $this->_prepareProducts($expectedProductsPrices);

        static::$handler->append(
            static::createMethodResponse(
                'v4/product/info/prices', [
                    'expected' => $expectedProductsPrices
                ]
            )
        );
    }

    protected function createProductsPricesGetter(): ProductsPricesSource
    {
        return new ProductsPricesSource([
            'token'    => 'token',
            'clientId' => 123321,
            'limit'    => 100,
            'client'   => static::createHttpClient(static::$handler)
        ]);
    }

    /**
     * Обработать объект ожидаемых цен товаров.
     *
     * @param array $productsPrices Цены товаров
     *
     * @return void
     */
    private function _prepareProducts(array $productsPrices): void
    {
        foreach ($productsPrices as $productPrices) {
            $productPrices->id     = (string) \random_int(100000000, 999999999);
            $productPrices->prices = \array_slice($productPrices->prices, 0, 3);

            $productPrices->prices[0]->id   = 'price';
            $productPrices->prices[0]->name = 'Price';

            $productPrices->prices[1]->id   = 'oldPrice';
            $productPrices->prices[1]->name = 'Old price';

            $productPrices->prices[2]->id   = 'minPrice';
            $productPrices->prices[2]->name = 'Min price';
        }
    }
}
