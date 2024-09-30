<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
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

use CashCarryShop\Sizya\DTO\OrderDTO;

/**
 * Событие на успешное создания заказов.
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class OrdersUpdated
{
    /**
     * Обновленные заказы.
     *
     * @var OrderDTO[]
     */
    public array $orders;

    /**
     * Создание события
     *
     * @param OrderDTO[] $orders Результат выполнения
     */
    public function __construct(array $orders)
    {
        $this->orders = $orders;
    }
}
