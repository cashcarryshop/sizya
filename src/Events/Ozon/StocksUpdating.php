<?php
/**
 * Событие на начало обновление остатков
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Events\Ozon;

/**
 * Событие на начало обновление остатков
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class StocksUpdating
{
    /**
     * Переданные данные об остатках
     *
     * @var array
     */
    public readonly array $stocks;

    /**
     * Создание события
     *
     * @param array $stocks Остатки
     */
    public function __construct(array $stocks)
    {
        $this->stocks = $stocks;
    }
}
