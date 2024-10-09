<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya;

use CashCarryShop\Sizya\DTO\OrderUpdateDTO;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;

/**
 * Интерфейс с методами для обновления заказов.
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface OrdersUpdaterInterface
{
    /**
     * Массово обновить заказы
     *
     * Количество возвращаемых элементов должно
     * соответствовать переданным.
     *
     * @param OrderUpdateDTO[] $orders Заказы
     *
     * @see OrderUpdateDTO
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return array<int, OrderDTO|ByErrorDTO>
     */
    public function massUpdateOrders(array $orders): array;

    /**
     * Обновить заказ по идентификатору
     *
     * @param OrderUpdateDTO $order Данные заказа
     *
     * @see OrderUpdateDTO
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return OrderDTO|ByErrorDTO
     */
    public function updateOrder(OrderUpdateDTO $order): OrderDTO|ByErrorDTO;
}
