<?php
/**
 * Этот файл является частью пакета sizya
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

use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\ErrorDTO;

/**
 * Интерфейс с методами для получения заказов
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface OrdersGetterInterface
{
    /**
     * Получить заказы
     *
     * @see OrderDTO
     *
     * @return OrderDTO[]
     */
    public function getOrders(): array;

    /**
     * Получить заказы по идентификаторам
     *
     * Заказы должны возвращаться в той-же последовательности
     * и в том же количестве, что и были переданы идентификаторы
     * в метод.
     *
     * @param array<string> $orderIds Идентификаторы заказов
     *
     * @see OrderDTO
     * @see ErrorDTO
     *
     * @return array<OrderDTO|ErrorDTO>
     */
    public function getOrdersByIds(array $orderIds): array;

    /**
     * Получить заказ по идентификатору
     *
     * @param string $orderId Идентификатор заказа
     *
     * @see OrderDTO
     * @see ErrorDTO
     *
     * @return OrderDTO|ErrorDTO
     */
    public function getOrderById(string $orderId): OrderDTO|ErrorDTO;
}
