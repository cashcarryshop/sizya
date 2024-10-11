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

use CashCarryShop\Sizya\DTO\PriceDTO;

/**
 * Трейт с методами для проверки цен.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait PricesAssertions
{
    /**
     * Сопоставить ожидаемые цены и полученые.
     *
     * @param array $expected Ожидаемые
     * @param array $prices   Цены
     *
     * @return void
     */
    protected function assertPrices(array $expected, array $prices): void
    {
        $this->assertSameSize(
            $expected,
            $prices,
            'Prices size must be equals'
        );

        \array_multisort(
            \array_column($expected, 'id'),
            SORT_STRING,
            $expected
        );

        \array_multisort(
            \array_column($prices, 'id'),
            SORT_STRING,
            $prices
        );

        \reset($expected);
        foreach ($prices as $price) {
            $this->assertPrice(\current($expected), $price);
            \next($expected);
        }
    }

    /**
     * Сопоставить ожидаемую цену и полученую.
     *
     * @param PriceDTO $expected Ожидаемая
     * @param PriceDTO $price    Цена
     *
     * @return void
     */
    protected function assertPrice(PriceDTO $expected, PriceDTO $price): void
    {
        $this->assertEquals(
            $expected->id,
            $price->id,
            'Price id is invalid'
        );

        $this->assertEquals(
            $expected->name,
            $price->name,
            'Price name is invalid'
        );

        $this->assertEquals(
            $expected->value,
            $price->value,
            'Price value is invalid'
        );
    }
}
