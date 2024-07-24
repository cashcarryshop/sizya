<?php
/**
 * Общее событие на ошибки
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Events;

use Throwable;

/**
 * Общее событие на ошибки
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class Error
{
    /**
     * Исключение
     *
     * @var Throwable
     */
    public readonly Throwable $reason;

    /**
     * Создание события
     *
     * @param Throwable $reason Причина ошибки
     */
    public function __construct(Throwable $reason)
    {
        $this->reason = $reason;
    }
}
