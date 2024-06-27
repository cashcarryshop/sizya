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
     * @var mixed
     */
    public readonly mixed $reason;

    /**
     * Создание события
     *
     * @param mixed $reason Причина ошибки
     */
    public function __construct(mixed $reason)
    {
        $this->reason = $reason;
    }
}
