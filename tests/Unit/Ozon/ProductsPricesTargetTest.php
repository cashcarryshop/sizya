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

use CashCarryShop\Sizya\Ozon\ProductsPricesTarget;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithOzon;
use CashCarryShop\Sizya\Tests\Traits\ProductsPricesUpdaterTests;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для обновления цен товаров Ozon.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(ProductsPricesTarget::class)]
class ProductsPricesTargetTest extends TestCase
{
    use InteractsWithOzon;
    use ProductsPricesUpdaterTests;

    protected function setUpBeforeTestUpdateProductsPrices(array $expected): void
    {
        $this->_prepareProducts($expected);

        static::$handler->append(
            static::createMethodResponse(
                'v1/product/import/prices', [
                    'expected' => $expected
                ]
            )
        );
    }

    protected function createProductsPricesUpdater(): ProductsPricesTarget
    {
        return new ProductsPricesTarget([
            'token'    => 'token',
            'clientId' => 123321,
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
