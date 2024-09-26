<?php declare(strict_types=1);
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
use CashCarryShop\Sizya\DTO\OrderCreateDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;

/**
 * Интерфейс с методами для создания заказов
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface OrdersCreatorInterface
{
    /**
     * Массово создать заказы
     *
     * Количество возвращаемых элементов должно
     * соответствовать переданным.
     *
     * @param OrderCreateDTO[] $orders Заказы для создания
     *
     * @see OrderCreateDTO
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return array<int, OrderDTO|ByErrorDTO>
     */
    public function massCreateOrders(array $orders): array;

    /**
     * Создать заказ
     *
     * @param OrderCreateDTO $order Заказ
     *
     * @see OrderCreateDTO
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return OrderDTO|ByErrorDTO
     */
    public function createOrder(OrderCreateDTO $order): OrderDTO|ByErrorDTO;
}
