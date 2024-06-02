<?php
/**
 * Событие на полученные остатки
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Events\Ozon;

/**
 * Событие на полученные остатки
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class StocksUpdated
{
    /**
     * Данные об обновлениях остатков
     *
     * @var array
     */
    public readonly array $updated;

    /**
     * Создание события
     *
     * @param array $updated Обновления остатков
     */
    public function __construct(array $updated)
    {
        $this->updated = $updated;
    }
}
