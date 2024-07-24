<?php
/**
 * Интерфейс с методами для обновления остатков
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
 * Интерфейс с методами для обновления остатков
 *
 * @category Sizya
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface StocksUpdaterInterface
{
    /**
     * Обновить остатки товаров по идентификаторам
     *
     * Массив $stocks должен быть:
     *
     * - id:           (string) Идентификатор товара
     * - warehouse_id: (string) Идентификатор склада
     * - quantity:     (int)    Количество остатков
     *
     * Должен возвращать массив:
     *
     * Поля id, warehouse_id и quantity могут быть
     * не возвращены, если во время обновления возникла
     * ошибка
     *
     * - optional(id):           (string) Идентификатор товара
     * - optional(warehouse_id): (string) Идентификатор склада
     * - optional(quantity):     (int)    Количество остатков
     * - error:                  (bool)   Возникла ли ошибка во время обновления
     * - optional(reason):       (mixed)  Если возникла ошибка, добавляется это поле
     * - original:               (mixed)  Оригинальный ответ
     *
     * Возвращаемый массив должен быть заполнен ровно
     * на то количество элементов, которые были
     * переданы в функцию.
     *
     * @param array $stocks Остатки
     *
     * @return array
     */
    public function updateStocksByIds(array $stocks): array;

    /**
     * Обновить остатки товаров по артикулам
     *
     * Массив $stocks должен быть:
     *
     * - article:       (string) Артикул товара
     * - warehouse_id:  (string) Идентификатор склада
     * - quantity:      (int)    Количество остатков
     *
     * Должен возвращать массив:
     *
     * Поля article, warehouse_id и quantity могут быть
     * не возвращены, если во время обновления возникла
     * ошибка
     *
     * - optional(article):      (string) Артикул товара
     * - optional(warehouse_id): (string) Идентификатор склада
     * - optional(quantity):     (int)    Количество остатков
     * - error:                  (bool)   Возникла ли ошибка во время обновления
     * - optional(reason):       (mixed)  Если возникла ошибка, добавляется это поле
     * - original:               (mixed)  Оригинальный ответ
     *
     * Возвращаемый массив должен быть заполнен ровно
     * на то количество элементов, которые были
     * переданы в функцию.
     *
     * @param array $stocks Остатки
     *
     * @return array
     */
    public function updateStocksByArticles(array $stocks): array;
}
