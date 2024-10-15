<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category TestMock
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Tests\Synchronizer;

use CashCarryShop\Sizya\DTO\ProductPricesDTO;
use CashCarryShop\Sizya\DTO\PriceDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\ProductsPricesGetterInterface;
use CashCarryShop\Synchronizer\SynchronizerSourceInterface;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithFakeData;

/**
 * Тестовый класс источника синхронизации цен товаров.
 *
 * @category TestMock
 * @package  Sizya
 * @author   TheWhatis <anton-gnogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class MockProductsPricesSource
    implements SynchronizerSourceInterface,
               ProductsPricesGetterInterface
{
    use InteractsWithFakeData;

    /**
     * Настройки.
     *
     * @var array
     */
    public array $settings;

    /**
     * Создать экземпляр источника.
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings = [])
    {
        $this->settings = \array_replace(
            [
                'articles' => $articles = \array_map(
                    fn () => static::fakeArticle(),
                    \array_fill(0, 10, null)
                ),
                'pricesIds' => $pricesIds = \array_map(
                    fn () => static::guidv4(),
                    \array_fill(0, 3, null)
                ),
                'items' => \array_map(
                    fn ($article) => static::fakeProductPricesDto([
                        'article' => $article,
                        'prices'  => \array_map(
                            fn ($id) => PriceDTO::fromArray([
                                'id'    => $id,
                                'name'  => static::fakeArticle(),
                                'value' => (float) \random_int(0, 10000)
                            ]),
                            $settings['pricesIds'] ?? $pricesIds
                        )
                    ]),
                    $settings['articles'] ?? $articles
                )
            ],
            $settings
        );
    }

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
        return $this->settings['items'];
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
        $items = [];

        foreach ($this->settings['items'] as $productPrices) {
            if (\in_array($productPrices->id, $productsIds)) {
                if ($pricesIds) {
                    $productPrices = clone $productPrices;
                    foreach ($productPrices->prices as $idx => $price) {
                        if (\in_array($price, $pricesIds)) {
                            continue;
                        }

                        unset($productPrices->prices[$idx]);
                    }

                    $productPrices->prices = \array_values($productPrices->prices);
                }

                $items[] = $productPrices;
                continue;
            }

            $items[] = ByErrorDTO::fromArray([
                'type'  => ByErrorDTO::NOT_FOUND,
                'value' => $productPrices->id
            ]);
        }

        return $items;
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
        return $this->getProductsPricesByIds([$productId], $pricesIds);
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
        $items = [];

        foreach ($this->settings['items'] as $productPrices) {
            if (\in_array($productPrices->article, $articles)) {
                if ($pricesIds) {
                    $productPrices = clone $productPrices;
                    foreach ($productPrices->prices as $idx => $price) {
                        if (\in_array($price, $pricesIds)) {
                            continue;
                        }

                        unset($productPrices->prices[$idx]);
                    }

                    $productPrices->prices = \array_values($productPrices->prices);
                }

                $items[] = $productPrices;
                continue;
            }

            $items[] = ByErrorDTO::fromArray([
                'type'  => ByErrorDTO::NOT_FOUND,
                'value' => $productPrices->article
            ]);
        }

        return $items;
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
        return $this->getProductsPricesByArticles([$article], $pricesIds)[0];
    }
}
