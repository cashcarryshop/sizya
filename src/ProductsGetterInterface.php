<?php
/**
 * Этот файл является частью пакета sizya
 *
 * PHP version 8
 *
 * @category Products
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya;

use CashCarryShop\Sizya\DTO\ProductDTO;
use CashCarryShop\Sizya\DTO\ErrorDTO;

/**
 * Интерфейс с методам для получения товаров
 *
 * @category Products
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
     * @see ProductDTO
     *
     * @return array<ProductDTO>
     */
    public function getProducts(): array;

    /**
     * Получить товар по идентификатору
     *
     * @see ProductDTO
     * @see ErrorDTO
     *
     * @param string $productId Идентификатор товара
     *
     * @return ProductDTO|ErrorDTO
     */
    public function getProductById(string $productId): ProductDTO|ErrorDTO;

    /**
     * Получить товары по идентификаторам
     *
     * @see ProductDTO
     * @see ErrorDTO
     *
     * @param array $productIds Идентификаторы товаров
     *
     * @return array<ProductDTO|ErrorDTO>
     */
    public function getProductsByIds(array $productIds): array;

    /**
     * Получить товар по артикулу
     *
     * @see ProductDTO
     * @see ErrorDTO
     *
     * @param string $article Артикул
     *
     * @return ProductDTO|ErrorDTO
     */
    public function getProductByArticle(string $article): ProductDTO|ErrorDTO;

    /**
     * Получить товары по артикулам
     *
     * @see ProductDTO
     * @see ErrorDTO
     *
     * @param array $articles Артикулы
     *
     * @return array<ProductDTO|ErrorDTO>
     */
    public function getProductsByArticles(array $articles): array;
}
