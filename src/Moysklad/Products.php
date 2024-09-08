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
use CashCarryShop\Sizya\DTO\ErrorDTO;
use GuzzleHttp\Promise\Utils as PromiseUtils;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\RequestException;
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
            'order'     => [['created', 'desc']],
            'priceType' => null
        ];

        parent::__construct(array_replace($defaults, $settings));
    }

    /**
     * Правила валидации настроек для
     * работы с товарами
     *
     * @return array
     */
    protected function rules(): array
    {
        return array_merge(
            parent::rules(), [
                'limit' => [
                    new Assert\Type('int'),
                    new Assert\Range(min: 100)
                ],
                'order' => new Assert\Collection([
                    0 => [
                        new Assert\Type('string'),
                        new Assert\Choice([
                            'created',
                            'name',
                            'id',
                            'article',
                        ])
                    ],
                    1 => [
                        new Assert\Type('string'),
                        new Assert\Choice(['asc', 'desc'])
                    ],
                ]),
                'priceType' => [
                    new Assert\Type(['string', 'null']),
                    new Assert\Length(36, 36)
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
     * Получить товар по идентификатору
     *
     * @see ProductsGetterInterface
     *
     * @param string $productId Идентификатор товара
     *
     * @return array<ProductDTO|ErrorDTO>
     */
    public function getProductById(string $productId): array
    {
        return $this->getProductsByIds([$productId])[0] ?? [];
    }

    /**
     * Получить товары по идентификаторам
     *
     * @see ProductsGetterInterface
     *
     * @param array $productIds Идентификаторы товаров
     *
     * @return array<ProductDTO|ErrorDTO>
     */
    public function getProductsByIds(array $productIds): array
    {
        return $this->_getByFilter('id', $productIds);
    }

    /**
     * Получить товар по артикулу
     *
     * @see ProductsGetterInterface
     *
     * @param string $article Артикул
     *
     * @return ProductDTO|ErrorDTO
     */
    public function getProductByArticle(string $article): ProductDTO|ErrorDTO
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
     * @return array<ProductDTO|ErrorDTO>
     */
    public function getProductsByArticles(array $articles): array
    {
        $byArticles = $this->_getByFilterWithPromise('article', $articles);
        $byCodes    = $this->_getByFilterWithPromise('code', $articles);

        return array_merge($byArticles->wait(), $byCodes->wait());
    }

    /**
     * Конвертировать Товар
     *
     * @param array $product Товар
     *
     * @return array<ProductDTO>
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

    /**
     * Получить элементы с помощью фильтров
     *
     * @param string $filter Название фильтра
     * @param array  $values Значение
     * @param int    $size   Размер чанка
     *
     * @return array<ProductDTO|ErrorDTO>
     */
    private function _getByFilter(string $filter, array $values, int $size = 80): array
    {
        return $this->_getByFilterWithPromise($filter, $values, $size)->wait();
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
     * @return PromiseInterface<ProductDTO|ErrorDTO>
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

            $promises[] = $this->send($clone->build('GET'));
        }

        return PromiseUtils::settle($promises)->then(
            function ($results) use ($size, $values) {
                $products = [];

                foreach ($results as $idx => $result) {
                    // Проверяем есть ли ошибки, если да, вместо
                    // их вывода добавляем ErrorDTO в результат
                    // выполнения.
                    if ($result['state'] === PromiseInterface::FULFILLED) {
                        // Если ошибок нет
                        if ($result['value']->getCode() < 300) {
                            try {
                                $products = array_merge(
                                    $products, array_map(
                                        fn ($product) => $this->_convertProduct($product),
                                        $this->decodeResponse($result['value'])['rows']
                                    )
                                );

                                continue;
                            } catch (Throwable $throwable) {
                                $reason = $throwable;
                            }
                        } else {
                            $reason = $result['value'];
                        }
                    } else {
                        $reason = $result['reason'] instanceof RequestException
                            ? $result['reason']->getResponse()
                            : $result['reason'];
                    }

                    // Если ошибки есть
                    for ($i = 0; $i < $size; ++$i) {
                        $products[] = ErrorDTO::fromArray([
                            'value'  => $values[$idx * $i],
                            'reason' => $reason
                        ]);
                    }
                }

                return $products;
            }
        );
    }
}
