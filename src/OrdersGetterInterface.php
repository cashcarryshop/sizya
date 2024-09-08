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
     * Все даты должны быть в формате UTC `Y-m-d\TH:i:s\Z`
     *
     * На выходе должен возвращать массив из заказов:
     *
     * - id:                        (string) Идентификатор заказа
     * - optional(article):         (string) Артикул заказа
     * - created:                   (string) Дата создания заказа
     * - status:                    (string) Статус заказа
     * - optional(shipment_date):   (string) Планируемая дата отгрузки
     * - optional(delivering_date): (string) Дата передачи заказа в доставку
     * - optional(description):     (string) Описание
     * - optional(additional)       (array)  Доп. данные
     * - positions:                 (array)  Массив с позициями
     * - original:                  (mixed)  Оригинальный ответ
     *
     * Массив с доп. данными:
     *
     * - optional(id):   (string) Идентификатор доп. поля
     * - entityId:       (string) Идентификатор доп. поля для создания
     * - optional(name): (string) Название доп. поля
     * - value:          (mixed)  Значение доп. поля
     * - original:       (mixed)  Оригинальные данные
     *
     * Массив с позициями:
     *
     * - id:                 (string) Идентификатор позиции
     * - orderId:            (string) Идентификатор товара
     * - optional(type)      (string) Тип товара
     * - article:            (string) Артикул товара
     * - quantity:           (int)    Количество товаров
     * - reserve:            (int)    Количество зарезервированных товаров
     * - price:              (float)  Цена товара
     * - discount:           (float)  Скидка
     * - optional(currency): (string) Валюта
     * - optional(vat):      (bool)   Учитывать ли НДС
     * - original:           (mixed)  Оригинальный ответ
     *
     * @return array
     */
    public function getOrders(): array;

    /**
     * Получить заказы по идентификаторам
     *
     * Должен возвращать массив с данными заказа,
     * смотреть `OrdersGetterInterface::getOrders`.
     *
     * @param array<string> $orderIds Идентификаторы заказов
     *
     * @return array
     */
    public function getOrdersByIds(array $orderIds): array;

    /**
     * Получить заказ по идентификатору
     *
     * Должен возвращать массив с данными заказа,
     * смотреть `OrdersGetterInterface::getOrders`.
     *
     * @param string $orderId Идентификатор заказа
     *
     * @return array
     */
    public function getOrderById(string $orderId): array;
}
