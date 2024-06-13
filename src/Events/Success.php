<?php
/**
 * Общее событие на успешное выполнение
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

/**
 * Общее событие на успешное выполнение
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class Success
{
    /**
     * Исключение
     *
     * @var mixed
     */
    public readonly mixed $result;

    /**
     * Создание события
     *
     * @param mixed $result Результат выполнения
     */
    public function __construct(mixed $result)
    {
        $this->result = $result;
    }
}
