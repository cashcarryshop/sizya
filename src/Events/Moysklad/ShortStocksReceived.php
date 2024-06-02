<?php
/**
 * Событие, когда краткий отчет
 * об остатках успешно получен
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Events\Moysklad;

/**
 * Событие, когда краткий отчет
 * об остатках успешно получен
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class ShortStocksReceived
{
    /**
     * Полученные остатки
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
