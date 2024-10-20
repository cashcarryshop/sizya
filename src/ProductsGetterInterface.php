<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
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
use CashCarryShop\Sizya\DTO\ByErrorDTO;

/**
 * Интерфейс с методам для получения товаров.
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
     * В отличии от остальных методов, может
     * выкинуть ошибку при выполнении.
     *
     * @see ProductDTO
     *
     * @return array<int, ProductDTO>
     */
    public function getProducts(): array;

    /**
     * Получить товар по идентификатору
     *
     * @param string $productId Идентификатор товара
     *
     * @see ProductDTO
     * @see ByErrorDTO
     *
     * @return ProductDTO|ByErrorDTO
     */
    public function getProductById(string $productId): ProductDTO|ByErrorDTO;

    /**
     * Получить товары по идентификаторам
     *
     * Количество возвращаемых элементов должно
     * соответствовать переданным или быть больше.
     *
     * Если в $productsIds было передано 2 одинаковых значения,
     * должна быть ошибка ByErrorDTO::DUPLICATE
     * или ByErrorDTO::VALIDATION.
     *
     * @param array $productsIds Идентификаторы товаров
     *
     * @see ProductDTO
     * @see ByErrorDTO
     *
     * @return array<int, ProductDTO|ByErrorDTO>
     */
    public function getProductsByIds(array $productsIds): array;

    /**
     * Получить товар по артикулу
     *
     * Количество возвращаемых элементов должно
     * соответствовать переданным или быть больше.
     *
     * @param string $article Артикул
     *
     * @see ProductDTO
     * @see ByErrorDTO
     *
     * @return ProductDTO|ByErrorDTO
     */
    public function getProductByArticle(string $article): ProductDTO|ByErrorDTO;

    /**
     * Получить товары по артикулам
     *
     * Количество возвращаемых элементов должно
     * соответствовать переданным или быть больше.
     *
     * Если в $articles было передано 2 одинаковых значения,
     * должна быть ошибка ByErrorDTO::DUPLICATE
     * или ByErrorDTO::VALIDATION.
     8
     * @param array $articles Артикулы
     *
     * @see ProductDTO
     * @see ByErrorDTO
     *
     * @return array<int, ProductDTO|ByErrorDTO>
     */
    public function getProductsByArticles(array $articles): array;
}
