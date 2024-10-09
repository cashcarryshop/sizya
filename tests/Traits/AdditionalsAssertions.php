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

use CashCarryShop\Sizya\DTO\AdditionalDTO;

/**
 * Трейт с тестами обновления заказов.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait AdditionalsAssertions
{
    /**
     * Сопоставить ожидаемое доп. поле и полученое.
     *
     * @param PositionDTO $expected   Ожидаемый
     * @param PositionDTO $additional Полученый
     *
     * @return void
     */
    protected function assertAdditional(
        AdditionalDTO $expected,
        AdditionalDTO $additional
    ): void {
        $this->assertEquals(
            $expected->id,
            $additional->id,
            'Additional id is invalid'
        );

        $this->assertEquals(
            $expected->entityId,
            $additional->entityId,
            'Additional entity id is invalid'
        );

        $this->assertEquals(
            $expected->name,
            $additional->name,
            'Additional name is invalid'
        );

        $this->assertEquals(
            $expected->value,
            $additional->value,
            'Additional value is invalid'
        );
    }
}
