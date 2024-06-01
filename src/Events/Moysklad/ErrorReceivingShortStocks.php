<?php
/**
 * Событие, когда краткий отчет
 * не был получен, входе ошибки
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Events\Moysklad;

use CashCarryShop\Sizya\Events\Error;
use Throwable;

/**
 * Событие, когда краткий отчет
 * не был получен, входе ошибки
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class ErrorReceivingShortStocks extends Error
{
    // ...
}
