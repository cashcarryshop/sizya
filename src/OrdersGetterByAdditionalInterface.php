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

use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;

/**
 * Интерфейс с методом для получение заказов по доп. полю.
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface OrdersGetterByAdditionalInterface
{
    /**
     * Получить заказы по доп. полю
     *
     * Количество возвращаемых элементов должно
     * соответствовать переданным или быть больше.
     *
     * Если в $values было передано 2 одинаковых значения,
     * должна быть ошибка ByErrorDTO::DUPLICATE
     * или ByErrorDTO::VALIDATION.
     *
     * @param string            $entityId Идентификатор сущности
     * @param array<int, mixed> $values   Значения доп. поля
     *
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return array<int, OrderDTO|ByErrorDTO>
     */
    public function getOrdersByAdditional(string $entityId, array $values): array;
}
