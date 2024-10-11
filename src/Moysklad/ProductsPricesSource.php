<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Moysklad;

use CashCarryShop\Sizya\ProductsPricesGetterInterface;
use CashCarryShop\Sizya\DTO\ProductPricesDTO;
use CashCarryShop\Sizya\DTO\PriceDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;

/**
 * Класс для работы с ценами товаров МойСклад.
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class ProductsPricesSource extends Products implements ProductsPricesGetterInterface
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
    public function getProductsPrices(array $pricesIds = []): array
    {
        return $this->_convert($this->getProducts(), $pricesIds);
    }

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
    public function getProductsPricesByIds(array $productsIds, array $pricesIds = []): array
    {
        return $this->_convert($this->getProductsByIds($productsIds), $pricesIds);
    }

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
    ): ProductPricesDTO|ByErrorDTO {
        return $this->_convert($this->getProductById($productId), $pricesIds);
    }

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
    public function getProductsPricesByArticles(array $articles, array $pricesIds = []): array
    {
        return $this->_convert($this->getProductsByArticles($articles), $pricesIds);
    }

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
    ): ProductPricesDTO|ByErrorDTO {
        return $this->_convert($this->getProductByArticle($article), $pricesIds);
    }

    /**
     * Конвертировать данные из Products
     *
     * @param array<int, ProductDTO|ByErrorDTO> $items     Элементы
     * @param string[]                          $pricesIds Идентификаторы цен
     *
     * @return ProductPricesDTO[]
     */
    private function _convert(array $items, array $pricesIds): array
    {
        return \array_map(
            function ($item) use ($pricesIds) {
                if ($item instanceof ByErrorDTO) {
                    $item->value = [
                        'value'     => $item->value,
                        'pricesIds' => $pricesIds
                    ];

                    return $item;
                }

                return ProductPricesDTO::fromArray([
                    'id'      => $item->id,
                    'article' => $item->article,
                    'prices'  => $this->_filtPrices($item->prices, $pricesIds)
                ]);
            },
            $items
        );
    }

    /**
     * Отфильтровать цены
     *
     * @param PriceDTO[] $prices    Цены
     * @param string[]   $pricesIds По каким ценам фильтровать
     *
     * @return PriceDTO[]
     */
    private function _filtPrices(array $prices, array $pricesIds): array
    {
        if ($pricesIds) {
            return \array_filter(
                static fn ($price) => \in_array($price->id, $pricesIds),
                $prices
            );
        }

        return $prices;
    }
}
