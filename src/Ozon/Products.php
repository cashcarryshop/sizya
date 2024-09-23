<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Ozon;

use CashCarryShop\Sizya\ProductsGetterInterface;
use CashCarryShop\Sizya\DTO\ProductDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\Utils as SizyaUtils;
use GuzzleHttp\Promise\Utils as PromiseUtils;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Класс для работы с товарами Ozon.
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see ProductsGetterInterface
 */
class Products extends AbstractSource implements ProductsGetterInterface
{
    /**
     * Создать экземпляр класс для работы с
     * товарами Ozon.
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        $defaults = [
            'limit'      => 100,
            'visibility' => 'VISIBLE',
        ];

        parent::__construct(\array_replace($defaults, $settings));
    }

    /**
     * Правила валидации настроек для
     * работы с товарами.
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
                'visibility' => [
                    new Assert\Type('string'),
                    new Assert\Choice([
                        // Все товары, кроме архивных.
                        'ALL',
                        // Товары, которые видны покупателям.
                        'VISIBLE',
                        // Товары, которые не видны покупателям.
                        'INVISIBLE',
                        // Товары, у которых не указано наличие.
                        'EMPTY_STOCK',
                        // Товары, которые не прошли модерацию.
                        'NOT_MODERATED',
                        // Товары, которые прошли модерацию.
                        'MODERATED',
                        // Товары, которые видны покупателям, но недоступны к покупке.
                        'DISABLED',
                        // Товары, создание которых завершилось ошибкой.
                        'STATE_FAILED',
                        // Товары, готовые к поставке.
                        'READY_TO_SUPPLY',
                        // Товары, которые проходят проверку валидатором на премодерации.
                        'VALIDATION_STATE_PENDING',
                        // Товары, которые не прошли проверку валидатором на премодерации.
                        'VALIDATION_STATE_FAIL',
                        // Товары, которые прошли проверку валидатором на премодерации.
                        'VALIDATION_STATE_SUCCESS',
                        // Товары, готовые к продаже.
                        'TO_SUPPLY',
                        // Товары в продаже.
                        'IN_SALE',
                        // Товары, скрытые от покупателей.
                        'REMOVED_FROM_SALE',
                        // Заблокированные товары.
                        'BANNED',
                        // Товары с завышенной ценой.
                        'OVERPRICED',
                        // Товары со слишком завышенной ценой.
                        'CRITICALLY_OVERPRICED',
                        // Товары без штрихкода.
                        'EMPTY_BARCODE',
                        // Товары со штрихкодом.
                        'BARCODE_EXISTS',
                        // Товары на карантине после изменения цены более чем на 50%.
                        'QUARANTINE',
                        // Товары в архиве.
                        'ARCHIVED',
                        // Товары в продаже со стоимостью выше, чем у конкурентов.
                        'OVERPRICED_WITH_STOCK',
                        // Товары в продаже с пустым или неполным описанием.
                        'PARTIAL_APPROVED',
                        // Товары без изображений.
                        'IMAGE_ABSENT',
                        // Товары, для которых заблокирована модерация.
                        'MODERATION_BLOCK'
                    ])
                ]
            ]
        );
    }

    /**
     * Получить товары
     *
     * @see ProductDTO
     *
     * @return array<int, ProductDTO>
     */
    public function getProducts(): array
    {
        $builder = $this->builder()->point('v2/product/list');

        $lastId     = '';
        $chunkLimit = \min($this->getSettings('limit'), 1000);

        $ids = [];

        do {
            $data = $this->decode(
                $this->send(
                    (clone $builder)
                        ->body([
                            'limit'   => $chunkLimit,
                            'last_id' => $lastId,
                            'filter'  => [
                                'visibility' => $this->getSettings('visibility')
                            ]
                        ])
                )
            );

            $ids = \array_merge(
                $ids,
                $chunk = \array_map(
                    static fn ($item) => $item['product_id'],
                    $data['result']['items']
                )
            );

            $lastId = $data['result']['last_id'] ?? '';
        } while (
            $lastId
                && \count($chunk) === $chunkLimit
                && \count($ids) < $this->getSettings('limit')
        );
        unset($chunk);
        unset($data);

        return $this->getProductsByIds($ids);
    }

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
    public function getProductById(string $productId): ProductDTO|ByErrorDTO
    {
        return $this->getProductsByIds([$productId])[0];
    }

    /**
     * Получить товары по идентификаторам
     *
     * @param array $productsIds Идентификаторы товаров
     *
     * @see ProductDTO
     * @see ByErrorDTO
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
                new Assert\Type('string')
            ]
        );

        $builder = $this->builder()->point('v2/product/info');

        $chunks   = [];
        $promises = [];

        foreach ($validated as $productId) {
            $chunks[] = [$productId];
            $promises[] = $this->send(
                (clone $builder)
                    ->body(['product_id' => $productId])
            );
        }

        return \array_merge(
            SizyaUtils::mapResults(
                $chunks,
                PromiseUtils::settle($promises)->wait(),
                function ($response, $chunk) {
                    $result = $this->decodeResponse($response)['result'];

                    return [
                        'dtos' => [ProductDTO::fromArray([
                            'id'       => $result['id'],
                            'article'  => $result['offer_id'],
                            'created'  => $result['created_at'],
                            'price'    => (float) $result['price'],
                            'original' => $result
                        ])],
                        'values' => [$result['id']]
                    ];
                }
            ),
            $errors
        );
    }

    /**
     * Получить товар по артикулу
     *
     * @param string $article Артикул
     *
     * @see ProductDTO
     * @see ByErrorDTO
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
     * @param array $articles Артикулы
     *
     * @see ProductDTO
     * @see ByErrorDTO
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
                new Assert\Type('string')
            ]
        );

        $builder = $this->builder()->point('v2/product/info');

        $chunks   = [];
        $promises = [];

        foreach ($validated as $article) {
            $chunks[] = [$article];
            $promises[] = $this->send(
                (clone $builder)
                    ->body(['offer_id' => $article])
            );
        }

        return \array_merge(
            SizyaUtils::mapResults(
                $chunks,
                PromiseUtils::settle($promises)->wait(),
                function ($response, $chunk) {
                    $result = $this->decodeResponse($response)['result'];

                    return [
                        'dtos' => [ProductDTO::fromArray([
                            'id'       => $result['id'],
                            'article'  => $result['offer_id'],
                            'created'  => $result['created_at'],
                            'price'    => (float) $result['price'],
                            'original' => $result
                        ])],
                        'values' => [$result['offer_id']]
                    ];
                }
            ),
            $errors
        );
    }
}
