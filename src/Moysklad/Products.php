<?php
/**
 * Этот файл является частью пакета sizya
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

use CashCarryShop\Sizya\ProductsGetterInterface;
use GuzzleHttp\Promise\Utils as PromiseUtils;
use GuzzleHttp\Promise\PromiseInterface;
use Respect\Validation\Validator as v;
use BadMethodCallException;

/**
 * Класс для работы с товарами МойСклад
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class Products extends AbstractSource implements ProductsGetterInterface
{
    /**
     * Создать экземпляр класс для работы с
     * товарами МойСклад
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        $defaults = [
            'limit' => 100,
            'order' => [['created', 'desc']]
        ];

        parent::__construct(array_replace($defaults, $settings));
        v::allOf(
            v::key('limit', v::intType()->min(100), false),
            v::key('priceType', v::stringType()->length(36, 36), false),
            v::key('order', v::each(
                v::key(0, v::stringType()->in([
                    'created',
                    'name',
                    'id',
                    'article',
                ])),
                v::key(1, v::stringType()->in('asc', 'desc'))
            ), false)
        )->assert($this->settings);
    }

    /**
     * Конвертировать Товар
     *
     * @param array $product Товар
     *
     * @return array
     */
    private function _convertProduct(array $product): array
    {
        if ($priceType = $this->getSettings('priceType')) {
            $price = null;
            foreach ($product['salePrices'] as $salePrice) {
                if ($salePrice['priceType']['id'] === $priceType) {
                    $price = $salePrice['value'];
                    break;
                }
            }
        }

        return [
            'id' => $product['id'],
            'article' => $product['meta']['type'] === 'variant'
                ? $product['code'] : $product['article'],
            'created' => Utils::dateToUtc($product['updated']),
            'price' => $price ?? $product['salePrices'][0]['value'] / 100,
            'original' => $product
        ];
    }

    /**
     * Получить товары
     *
     * Смотреть `ProductsInterface::getProducts`
     *
     * @return array
     */
    public function getProducts(): array
    {
        $builder = $this->builder()->point('entity/assortment');

        foreach ($this->getSettings('order') as $order) {
            $builder->order(...$order);
        }

        $offset = 0;
        $maxOffset = $counter = $this->getSettings('limit');

        $products = [];

        do {
            $clone = (clone $builder)
                ->offset($offset)
                ->limit($counter > 100 ? 100 : $counter);

            $chunk = $this->decode($this->send($clone->build('GET')))->wait();
            $products = array_merge(
                $products, array_map(
                    fn ($product) => $this->_convertProduct($product),
                    $chunk['rows']
                )
            );

            $offset += 100;
            $counter -= 100;
        } while (count($chunk['rows']) === 100 && $offset < $maxOffset);

        return $products;
    }

    /**
     * Получить элементы с помощью фильтров
     *
     * Возвращает PromiseInterface
     *
     * @param string $filter Название фильтра
     * @param array  $values Значение
     * @param int    $size   Размер чанка
     *
     * @return array
     */
    private function _getByFilterWithPromise(
        string $filter,
        array $values,
        int $size = 80
    ): PromiseInterface {
        $builder = $this->builder()->point('entity/assortment');

        $promises = [];
        foreach (array_chunk($values, $size) as $chunk) {
            $clone = clone $builder;

            foreach ($chunk as $value) {
                $clone->filter($filter, $value);
            }

            $promises[] = $this->decode($this->send($clone->build('GET')));
        }

        return PromiseUtils::all($promises)->then(
            function ($results) {
                $products = [];

                foreach ($results as $result) {
                    $products = array_merge(
                        $products, array_map(
                            fn ($product) => $this->_convertProduct($product),
                            $result['rows']
                        )
                    );
                }

                return $products;
            }
        );
    }

    /**
     * Получить элементы с помощью фильтров
     *
     * @param string $filter Название фильтра
     * @param array  $values Значение
     * @param int    $size   Размер чанка
     *
     * @return array
     */
    private function _getByFilter(string $filter, array $values, int $size = 80): array
    {
        return $this->_getByFilterWithPromise($filter, $values, $size)->wait();
    }

    /**
     * Получить товары по идентификаторам
     *
     * Смотреть `ProductsInterface::getProductsByIds`
     *
     * @param array $productIds Идентификаторы товаров
     *
     * @return array
     */
    public function getProductsByIds(array $productIds): array
    {
        return $this->_getByFilter('id', $productIds);
    }

    /**
     * Получить товар по идентификатору
     *
     * Смотреть `ProductsInterface::getProductById`
     *
     * @param string $productId Идентификатор товара
     *
     * @return array
     */
    public function getProductById(string $productId): array
    {
        return $this->getProductsByIds([$productId])[0] ?? [];
    }

    /**
     * Получить товары по артикулам
     *
     * Смотреть `ProductsInterface::getProductsByArticles`
     *
     * @param array $articles Артикулы
     *
     * @return array
     */
    public function getProductsByArticles(array $articles): array
    {
        $byArticle = $this->_getByFilterWithPromise('article', $articles);
        $byCodes = $this->_getByFilterWithPromise('code', $articles);

        return array_merge(
            $byArticle->wait(),
            $byCodes->wait()
        );
    }

    /**
     * Получить товар по артикулу
     *
     * Смотреть `ProductsInterface::getProductByArticle`
     *
     * @param string $article Артикул
     *
     * @return array
     */
    public function getProductByArticle(string $article): array
    {
        return $this->getProductsByArticles([$article])[0] ?? [];
    }
}
