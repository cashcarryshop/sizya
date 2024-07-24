<?php
/**
 * Интерфейс с методом для получение заказов по доп. полю
 *
 * PHP version 8
 *
 * @category Sizya
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya;

/**
 * Интерфейс с методом для получение заказов по доп. полю
 *
 * @category Sizya
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
     * Должен вовращать тот-же формат данных
     * что и `OrdersGetterInterface::get`, только
     * в этом случае поле `additional` обязательно
     *
     * @param string $entityId Идентификатор сущности
     * @param array  $values   Значения доп. поля
     *
     * @return array
     */
    public function getOrdersByAdditional(string $entityId, array $values): array;
}
