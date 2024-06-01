<?php
/**
 * Событие, когда остатки не были
 * обновлены в ходе ошибки
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

use CashCarryShop\Sizya\Events\Error;
use Throwable;

/**
 * Событие, когда остатки не были
 * обновлены в ходе ошибки
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class ErrorUpdatingStocks extends Error
{
    // ...
}
