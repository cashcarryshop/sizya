<?php
/**
 * Интерфейс с методами для создания заказов
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
     * Все даты должны быть в формате UTC `Y-m-d\TH:i:s\Z`
     *
     * Массив $orders должен быть:
     *
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
     * `orderId` и `article` взаимозаменяемые, обязательные.
     *
     * - orderId|article:    (string) Идентификатор или артикул товара
     * - optional(type)      (string) Тип товара
     * - optional(quantity): (int)    Количество товаров
     * - optional(reserve):  (int)    Количество зарезервированных товаров
     * - optional(price):    (float)  Цена товара
     * - optional(discount): (float)  Скидка
     * - optional(currency): (string) Валюта
     * - optional(vat):      (bool)   Учитывать ли НДС
     *
     * Должен возвращать массив:
     *
     * Поля id, article, created, status, shipment_date,
     * delivery_date, additional, positions могут быть не
     * возвращены, если во время обновления возникла ошибка.
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
     * @return array<array>
     */
    public function massCreateOrders(array $orders): array;

    /**
     * Создать заказ
     *
     * Должен возвращать данные с созданным заказом,
     * смотреть `OrdersCreatorInterface::massCreateOrders`.
     *
     * @param array $order Заказ
     *
     * @return array
     */
    public function createOrder(array $order): array;
}
