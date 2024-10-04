<?php declare(strict_types=1);
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
use CashCarryShop\Sizya\DTO\ProductDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\Utils as SizyaUtils;
use GuzzleHttp\Promise\Utils as PromiseUtils;
use GuzzleHttp\Promise\PromiseInterface;
use Symfony\Component\Validator\Constraints as Assert;

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
            'limit'        => 100,
            'groupBy'      => 'consignment',
            'priceType'    => null,
            'minPriceType' => null
        ];

        parent::__construct(\array_replace($defaults, $settings));
    }

    /**
     * Правила валидации настроек для
     * работы с товарами
     *
     * @return array
     */
    protected function rules(): array
    {
        return \array_merge(
            parent::rules(), [
                'limit' => [
                    new Assert\Type('int'),
                    new Assert\Range(min: 100)
                ],
                'groupBy' => [
                    new Assert\Type('string'),
                    new Assert\Choice(['consignment', 'variant', 'product'])
                ],
                'priceType' => [
                    new Assert\Type(['string', 'null']),
                    new Assert\Uuid(strict: false)
                ],
                'minPriceType' => [
                    new Assert\Type(['string', 'null']),
                    new Assert\Uuid(strict: false)
                ]
            ]
        );
    }

    /**
     * Получить товары
     *
     * @see ProductsGetterInterface
     *
     * @return array<int, ProductDTO>
     */
    public function getProducts(): array
    {
        $builder = $this->builder()->point('entity/assortment');

        if ($groupBy = $this->getSettings('groupBy')) {
            $builder->param('groupBy', $groupBy);
        }
        unset($groupBy);

        $products = Utils::getAll(
            $builder,
            $this->getSettings('limit'),
            min($this->getSettings('limit'), 1000),
            [$this, 'send'],
            fn ($response) => \array_map(
                fn ($product) => $this->_convertProduct($product),
                $this->decodeResponse($response)['rows']
            )
        );

        return $products;
    }

    /**
     * Получить товар по идентификатору
     *
     * @see ProductsGetterInterface
     *
     * @param string $productId Идентификатор товара
     *
     * @return ProductDTO|ByErrorDTO
     */
    public function getProductById(string $productId): ProductDTO|ByErrorDTO
    {
        return $this->getProductsByIds([$productId])[0] ?? [];
    }

    /**
     * Получить товары по идентификаторам
     *
     * @see ProductsGetterInterface
     *
     * @param array $productsIds Идентификаторы товаров
     *
     * @return array<int, ProductDTO|ByErrorDTO>
     */
    public function getProductsByIds(array $productsIds): array
    {
        [
            $validated,
            $errors
        ] = SizyaUtils::splitByValidationErrors(
            $this->getValidator(), $productsIds, [
                new Assert\NotBlank,
                new Assert\Type('string'),
                new Assert\Uuid(strict: false)
            ]
        );
        unset($productsIds);

        return \array_merge(
            $this->_getByFilter('id', $validated)->wait(),
            $errors
        );
    }

    /**
     * Получить товар по артикулу
     *
     * @see ProductsGetterInterface
     *
     * @param string $article Артикул
     *
     * @return ProductDTO|ByErrorDTO
     */
    public function getProductByArticle(string $article): ProductDTO|ByErrorDTO
    {
        return $this->getProductsByArticles([$article])[0];
    }

    /**
     * Получить товары по артикулам
     *
     * @see ProductsGetterInterface
     *
     * @param array $articles Артикулы
     *
     * @return array<int, ProductDTO|ByErrorDTO>
     */
    public function getProductsByArticles(array $articles): array
    {
        [
            $validated,
            $errors
        ] = SizyaUtils::splitByValidationErrors(
            $this->getValidator(), $articles, [
                new Assert\NotBlank,
                new Assert\Length(
                    max: 3072,
                    countUnit: Assert\Length::COUNT_BYTES
                )
            ]
        );
        unset($articles);

        $byArticles = $this->_getByFilter('article', $validated);
        $byCodes    = $this->_getByFilter('code',    $validated, 'article');

        return \array_merge(
            PromiseUtils::all([$byArticles, $byCodes])->then(
                static function ($results) {
                    [$byArticles, $byCodes] = $results;

                    $products = [];
                    foreach ($byArticles as $idx => $byArticle) {
                        if ($byArticle instanceof ByErrorDTO
                            && !($byCodes[$idx] instanceof ByErrorDTO)
                        ) {
                            $products[$idx] = $byCodes[$idx];
                            continue;
                        }

                        $products[$idx] = $byArticle;
                    }

                    return $products;
                }
            )->wait(),
            $errors
        );
    }

    /**
     * Получить элементы с помощью фильтров
     *
     * Возвращает PromiseInterface
     *
     * @param string $filter    Название фильтра
     * @param array  $values    Значение
     * @param string $field     Название поля в dto по которому производиться поиск
     *
     * @return PromiseInterface<ProductDTO|ByErrorDTO>
     */
    private function _getByFilter(
        string  $filter,
        array   $values,
        string  $field = null
    ): PromiseInterface {
        $field = $field ? $field : $filter;

        return Utils::getByFilter(
            $filter,
            $values,
            $this->builder()->point('entity/assortment'),
            [$this, 'send'],
            function ($response, $chunk) use ($field) {
                $dtos   = [];
                $values = [];

                foreach ($this->decodeResponse($response)['rows'] as $item) {
                    $values[] = (
                        $dtos[] = $this->_convertProduct($item)
                    )->$field;
                }

                return [
                    'dtos'   => $dtos,
                    'values' => $values
                ];
            },
        );
    }

    /**
     * Конвертировать Товар
     *
     * @param array $product Товар
     *
     * @return ProductDTO
     */
    private function _convertProduct(array $product): ProductDTO
    {
        $price    = null;
        $minPrice = null;

        $priceType = $this->getSettings('priceType');
        $minPriceType = $this->getSettings('minPriceType');

        if ($priceType || $minPriceType) {
            $break = $minPriceType === null;

            foreach ($product['salePrices'] as $salePrice) {
                if ($salePrice['priceType']['id'] === $minPriceType) {
                    $minPrice = $salePrice['value'];
                    $break = true;
                }

                if ($salePrice['priceType']['id'] === $priceType) {
                    $price = $salePrice['value'];
                }

                if ($price && $break) {
                    break;
                }
            }
        }
        unset($priceType);
        unset($minPriceType);

        $price    ??= (float) $product['salePrices'][0]['value'] / 100;
        $minPrice ??= $price;

        $article = $product['meta']['type'] === 'variant'
            ? $product['code']
            : $product['article'];

        return ProductDTO::fromArray([
            'id'       => $product['id'],
            'article'  => $article,
            'created'  => Utils::dateToUtc($product['updated']),
            'price'    => $price,
            'minPrice' => $minPrice,
            'original' => $product
        ]);
    }
}
