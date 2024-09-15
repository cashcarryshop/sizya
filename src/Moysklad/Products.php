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
            'limit'     => 100,
            'groupBy'   => 'consignment',
            'priceType' => null
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
                ]
            ]
        );
    }

    /**
     * Получить товары
     *
     * @see ProductsGetterInterface
     *
     * @return array<ProductDTO>
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
     * @return array<ProductDTO|ByErrorDTO>
     */
    public function getProductsByIds(array $productsIds): array
    {
        [
            $validated,
            $errors
        ] = SizyaUtils::splitByValidationErrors(
            $productsIds,
            $this->getSettings('validator')
                ->validate(
                    $productsIds, new Assert\All([
                        new Assert\Type('string'),
                        new Assert\NotBlank,
                        new Assert\Uuid(strict: false)
                    ])
                )
        );
        unset($productsIds);

        $products = $this->_getByFilter('id', $validated)->wait();
        unset($validated);

        foreach ($errors as $error) {
            $products[] = ByErrorDTO::fromArray([
                'type'   => ByErrorDTO::VALIDATION,
                'reason' => $error,
                'value'  => $error->value
            ]);
        }
        unset($errors);

        return $products;
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
     * @return array<ProductDTO|ByErrorDTO>
     */
    public function getProductsByArticles(array $articles): array
    {
        [
            $validated,
            $errors
        ] = SizyaUtils::splitByValidationErrors(
            $articles,
            $this->getSettings('validator')
                ->validate($articles, new Assert\All([
                    new Assert\NotBlank,
                    new Assert\Length(max: 3072, countUnit: Assert\Length::COUNT_BYTES)
                ]))
        );
        unset($articles);

        $byArticles = $this->_getByFilter('article', $validated);
        $byCodes    = $this->_getByFilter('code',    $validated, field: 'article');

        $products = PromiseUtils::all([$byArticles, $byCodes])->then(
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
        )->wait();
        unset($validated);

        foreach ($errors as $error) {
            $products[] = ByErrorDTO::fromArray([
                'type'   => ByErrorDTO::VALIDATION,
                'reason' => $error,
                'value'  => $error->value
            ]);
        }
        unset($errors);

        return $products;
    }

    /**
     * Получить элементы с помощью фильтров
     *
     * Возвращает PromiseInterface
     *
     * @param string $filter    Название фильтра
     * @param array  $values    Значение
     * @param int    $chunkSize Размер чанка
     * @param string $field     Название поля в dto по которому производиться поиск
     *
     * @return PromiseInterface<ProductDTO|ByErrorDTO>
     */
    private function _getByFilter(
        string  $filter,
        array   $values,
        int     $chunkSize = 3072,
        string  $field     = null
    ): PromiseInterface {
        $field = $field ? $field : $filter;

        return Utils::getByFilter(
            $filter,
            $values,
            $this->builder()->point('entity/assortment'),
            [$this, 'send'],
            function ($response) {
                return \array_map(
                    fn ($item) => $this->_convertProduct($item),
                    $this->decodeResponse($response)['rows']
                );
            },
            static fn ($product) => $product->$field
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
        if ($priceType = $this->getSettings('priceType')) {
            $price = null;
            foreach ($product['salePrices'] as $salePrice) {
                if ($salePrice['priceType']['id'] === $priceType) {
                    $price = $salePrice['value'];
                    break;
                }
            }
        }

        $article = $product['meta']['type'] === 'variant'
            ? $product['code']
            : $product['article'];

        return ProductDTO::fromArray([
            'id'       => $product['id'],
            'article'  => $article,
            'created'  => Utils::dateToUtc($product['updated']),
            'price'    => (float) ($price ?? $product['salePrices'][0]['value'] / 100),
            'original' => $product
        ]);
    }
}
