<?php
/**
 * Интерфейс с методами для получения остатков
 *
 * PHP version 8
 *
 * @category Stocks
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya;

/**
 * Интерфейс с методами для получения остатков
 *
 * @category Stocks
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface StocksGetterInterface
{
    /**
     * Получить остатки товаров
     *
     * Возвращает массив с остатками товаров:
     *
     * - id:           (string) Идентификатор товара
     * - article:      (string) Артикул товара
     * - warehouse_id: (string) Идентификатор склада
     * - quantity:     (int)    Количество товара на складе
     * - original:     (mixed)  Оригинальный ответ
     *
     * @return array
     */
    public function getStocks(): array;
}
