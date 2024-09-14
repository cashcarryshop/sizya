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
use CashCarryShop\Sizya\DTO\ByErrorDTO;

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
     * Количество возвращаемых элементов должно
     * соответствовать переданным.
     *
     * @param array<string> $orderIds Идентификаторы заказов
     *
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return array<int, OrderDTO|ByErrorDTO>
     */
    public function getOrdersByIds(array $orderIds): array;

    /**
     * Получить заказ по идентификатору
     *
     * @param string $orderId Идентификатор заказа
     *
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return OrderDTO|ByErrorDTO
     */
    public function getOrderById(string $orderId): OrderDTO|ByErrorDTO;
}
