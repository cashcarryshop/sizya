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

use CashCarryShop\Sizya\DTO\PositionDTO;

/**
 * Трейт с методами для проверки позиций.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait PositionsAssertions
{
    /**
     * Сопоставить ожидаемую позицию и полученую.
     *
     * @param PositionDTO $expected Ожидаемый
     * @param PositionDTO $position Полученый
     *
     * @return void
     */
    protected function assertPosition(
        PositionDTO $expected,
        PositionDTO $position
    ): void {
        $this->assertEquals(
            $expected->id,
            $position->id,
            'Position is is invalid'
        );

        $this->assertEquals(
            $expected->productId,
            $position->productId,
            'Position product id is invalid'
        );

        $this->assertEquals(
            $expected->article,
            $position->article,
            'Position product article is invalid'
        );

        $this->assertEquals(
            $expected->type,
            $position->type,
            'Position product type is invalid'
        );

        $this->assertEquals(
            $expected->quantity,
            $position->quantity,
            'Position quantity is invalid'
        );

        $this->assertEquals(
            $expected->reserve,
            $position->reserve,
            'Position reserve is invalid'
        );

        $this->assertEquals(
            $expected->price,
            $position->price,
            'Position price is invalid'
        );

        $this->assertEquals(
            $expected->discount,
            $position->discount,
            'Position discount is invalid'
        );

        $this->assertEquals(
            $expected->currency,
            $position->currency,
            'Poition currency is invalid'
        );

        $this->assertEquals(
            $expected->vat,
            $position->vat,
            'Position vat is invalid'
        );
    }
}
