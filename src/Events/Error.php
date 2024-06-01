<?php
/**
 * Общее событие на ошибки
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
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
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
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
    public readonly Throwable $exception;

    /**
     * Создание события
     *
     * @param Throwale $exception Исключение
     */
    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }
}
