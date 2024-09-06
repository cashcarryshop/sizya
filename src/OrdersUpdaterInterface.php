<?php
/**
 * Интерфейс с методами для обновления заказов
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

/**
 * Интерфейс с методами для обновления заказов
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
     * Все даты должны быть в формате UTC `Y-m-d\TH:i:s\Z`
     *
     * Массив $orders должен быть:
     *
     * - id:                      (string)  Идентификатор заказа
     * - optional(article):       (string)  Артикул заказа
     * - optional(created):       (string)  Дата создания заказа
     * - optional(status):        (?string) Статус заказа
     * - optional(shipment_date): (?string) Планируемая дата отгрузки
     * - optional(delivery_date): (?string) Планируемая дата доставки
     * - optional(description):   (?string) Описание
     * - optional(additional)     (array)   Доп. данные
     * - optional(positions):     (array)   Массив с позициями
     *
     * Позиции `positions`:
     *
     * Если передать `id` вместе с `orderId` или `article`, то для позиции
     * с `id` товар изменится, а если вместе с ним не передать остальные
     * параметры, то значения сбросятся до стандартных.
     *
     * Если передать `orderId` или `article` без `id`, то можно обновлять
     * данные позиции, в которой находиться этот товар.
     *
     * Если передать `orderId` или `article` товара, которого нет в заказе,
     * то будет создана новая позиция с этим товаром.
     *
     * - optional(id):               (string)  Идентификатор позиции
     * - optional(orderId|article):  (?string) Идентификатор или артикул товара
     * - optional(quantity):         (int)     Количество товаров
     * - optional(reserve):          (int)     Количество зарезервированных товаров
     * - optional(price):            (float)   Цена товара
     * - optional(discount):         (float)   Скидка
     * - optional(currency):         (string)  Валюта
     * - optional(vat):              (bool)    Учитывать ли НДС
     *
     * Должен возвращать массив:
     *
     * Поля id, article, created, status, shipment_date,
     * delivery_date, additional, positions могут быть не
     * возвращены, если во время обновления возникла ошибка.
     *
     * Массив с позициями должен возвращать как
     * это делается с помощью метода `OrdersGetterInterface::get`
     *
     * - optional(id):            (string) Идентификатор заказа
     * - optional(article):       (string) Артикул заказа
     * - optional(created):       (string) Дата создания заказа
     * - optional(status):        (string) Статус заказа
     * - optional(shipment_date): (string) Планируемая дата отгрузки
     * - optional(delivery_date): (string) Планируемая дата доставки
     * - optional(description):   (string) Описание
     * - optional(additional)     (array)  Доп. данные
     * - optional(positions):     (array)  Массив с позициями
     * - error:                   (bool)   Возникла ли ошибка во время обновления
     * - optional(reason):        (mixed)  Если возникла ошибка, добавляется это поле
     * - original:                (mixed)  Оригинальный ответ
     *
     * Возвращаемый массив должен быть заполнен ровно
     * на то количество элементов, которые были
     * переданы в функцию и возвращаться в той-же
     * последовательности.
     *
     * @param array $orders Заказы
     *
     * @return array
     */
    public function massUpdateOrders(array $orders): array;

    /**
     * Обновить заказ по идентификатору
     *
     * Должен возвращать данные с обновленным заказом,
     * смотреть `OrdersUpdaterInterface::massUpdateOrders`.
     *
     * @param array $order Данные заказа (обязательна передача id)
     *
     * @return array
     */
    public function updateOrder(array $order): array;
}
