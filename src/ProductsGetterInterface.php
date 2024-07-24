<?php
/**
 * Интерфейс с методам для получения товаров
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
 * Интерфейс с методам для получения товаров
 *
 * @category Sizya
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface ProductsGetterInterface
{
    /**
     * Получить товары
     *
     * Возвращает массив с товарами:
     *
     * - id: (string) Идентификатор товара
     * - article: (string) Артикул товара
     * - created: (string) Дата создания товара (в формате UTC Y-m-d\TH:i:s\Z)
     * - price: (float) Цена товара
     * - original: (mixed) Оригинальный ответ
     *
     * @return array
     */
    public function getProducts(): array;

    /**
     * Получить товары по идентификаторам
     *
     * Должен возвращать массив с данными товаров,
     * смотреть `ProductsGetterInterface::getProducts`.
     *
     * @param array $productIds Идентификаторы товаров
     *
     * @return array
     */
    public function getProductsByIds(array $productIds): array;

    /**
     * Получить товар по идентификатору
     *
     * Должен возвращать массив с данными товара,
     * смотреть `ProductsGetterInterface::getProducts`.
     *
     * @param string $productId Идентификатор товара
     *
     * @return array
     */
    public function getProductById(string $productId): array;

    /**
     * Получить товары по артикулам
     *
     * Должен возвращать массив с данными товаров,
     * смотреть `ProductsGetterInterface::getProducts`.
     *
     * @param array $articles Артикулы
     *
     * @return array
     */
    public function getProductsByArticles(array $articles): array;

    /**
     * Получить товар по артикулу
     *
     * Должен возвращать массив с данными товара,
     * смотреть `ProductsGetterInterface::getProducts`.
     *
     * @param string $article Артикул
     *
     * @return array
     */
    public function getProductByArticle(string $article): array;
}
