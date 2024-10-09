w<?php declare(strict_types=1);
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

use CashCarryShop\Sizya\DTO\ProductPricesDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;

/**
 * Интерфейс с методам для получения цен товаров.
 *
 * @category Products
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface ProductsPricesGetterInterface
{
    /**
     * Получить цены товаров.
     *
     * @param array $pricesIds Фильтры по идентификаторам цен
     *
     * @see ProductPricesDTO
     *
     * @return ProductPricesDTO[]
     */
    public function getProductsPrices(array $pricesIds = []): array;

    /**
     * Получить цены товаров по идентификаторам товаров.
     *
     * @param string[] $productsIds Идентификаторы товаров
     * @param string[] $pricesIds   Фильтры по идентификаторам цен
     *
     * @see ProductPriceDTO
     * @see ByErrorDTO
     *
     * @return array<int, ProductPricesDTO|ByErrorDTO>
     */
    public function getProductsPricesByIds(array $productsIds, array $pricesIds = []): array;

    /**
     * Получить цены товаров по идентификаторам товаров.
     *
     * Может возникнуть ошибка при получении цен.
     *
     * @param string   $productId Идентификаторы товаров
     * @param string[] $pricesIds Фильтровать по идентификаторам цен
     *
     * @see ProductPricesDTO
     * @see ByErrorDTO
     *
     * @return ProductPricesDTO|ByErrorDTO
     */
    public function getProductPricesById(
        string $productId,
        array  $pricesIds = []
    ): ProductPricesDTO|ByErrorDTO;

    /**
     * Получить цены товаров по артикулам товаров.
     *
     * @param string[] $articles  Идентификаторы товаров
     * @param string[] $pricesIds Фильтровать по идентификаторам цен
     *
     * @see ProductPricesDTO
     * @see ByErrorDTO
     *
     * @return array<int, ProductPriceDTO|ByErrorDTO>
     */
    public function getProductsPricesByArticles(array $articles, array $pricesIds = []): array;

    /**
     * Получить цены товаров по идентификаторам товаров.
     *
     * Может возникнуть ошибка при получении цен.
     *
     * @param string   $article   Идентификаторы товаров
     * @param string[] $pricesIds Фильтровать по идентификаторам цен
     *
     * @see ProductPricesDTO
     * @see ByErrorDTO
     *
     * @return ProductPricesDTO[]
     */
    public function getProductPricesByArticle(
        string $article,
        array  $pricesIds = []
    ): ProductPricesDTO|ByErrorDTO;
}
