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

use CashCarryShop\Sizya\ProductsPricesGetterInterface;
use CashCarryShop\Sizya\DTO\ProductPricesDTO;
use CashCarryShop\Sizya\DTO\PriceDTO;
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
 * @see ProductsPricesGetterInterface
 */
class ProductsPricesSource extends AbstractSource
    implements ProductsPricesGetterInterface
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
            'visibility' => 'ALL',
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
     * Получить цены товаров
     *
     * @param array $pricesIds Фильтры по идентификаторам цен
     *
     * @see ProductPricesDTO
     *
     * @return ProductPricesDTO[]
     */
    public function getProductsPrices(array $pricesIds = []): array
    {
        $builder = $this->builder()->point('v4/product/info/prices');

        $lastId     = '';
        $chunkLimit = \min($this->getSettings('limit'), 1000);

        $ids = [];

        $productsPrices = [];
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
                        ])->build('POST')
                )
            )->wait();

            foreach ($data['result']['items'] as $item) {
                $productsPrices[] = $this->_convert($item, $pricesIds);
            }

            $lastId = $data['result']['last_id'] ?? '';
        } while (
            $lastId
                && \count($data['result']['items']) === $chunkLimit
                && \count($ids) < $this->getSettings('limit')
        );

        return $productsPrices;
    }

    /**
     * Получить товар по идентификатору
     *
     * @param string $productId Идентификатор товара
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
        return $this->getProductsPricesByIds([$productId], $pricesIds)[0];
    }

    /**
     * Получить товары по идентификаторам
     *
     * @param array $productsIds Идентификаторы товаров
     * @param array $pricesIds   Фильтры по идентификаторам цен
     *
     * @see ProductPricesDTO
     * @see ByErrorDTO
     *
     * @return array<int, ProductPricesDTO|ByErrorDTO>
     */
    public function getProductsPricesByIds(
        array $productsIds,
        array $pricesIds = []
    ): array {
        return $this->_getByFilter('product_id', $productsIds, $pricesIds);
    }

    /**
     * Получить товар по артикулу
     *
     * @param string $article   Артикул
     * @param array  $pricesIds Фильтры по идентификаторам цен
     *
     * @see ProductPricesDTO
     * @see ByErrorDTO
     *
     * @return ProductDTO|ByErrorDTO
     */
    public function getProductPricesByArticle(
        string $article,
        array  $pricesIds = []
    ): ProductPricesDTO|ByErrorDTO {
        return $this->getProductsPricesByArticles([$article], $pricesIds)[0];
    }

    /**
     * Получить товары по артикулам
     *
     * @param array $articles  Артикулы
     * @param array $pricesIds Фильтры по идентификаторам цен
     *
     * @see ProductPricesDTO
     * @see ByErrorDTO
     *
     * @return array<int, ProductDTO|ByErrorDTO>
     */
    public function getProductsPricesByArticles(array $articles, array $pricesIds = []): array
    {
        return $this->_getByFilter('offer_id', $articles, $pricesIds);
    }

    /**
     * Получить товары по фильтру.
     *
     * @param string $filter    Фильтр
     * @param array  $values    Значения
     * @param array  $pricesIds Фильтры по идентификаторам цен
     *
     * @return array
     */
    private function _getByFilter(
        string $filter,
        array  $values,
        array  $pricesIds
    ): array {
        [
            $validated,
            $errors
        ] = SizyaUtils::splitByValidationErrors(
            $this->getValidator(), $values, [
                new Assert\NotBlank,
                new Assert\Type('string')
            ]
        );

        $builder = $this->builder()->point('v4/product/info/prices');

        $chunks   = \array_chunk($validated, 1000);
        $promises = [];
        foreach ($chunks as $chunk) {
            $promises[] = $this->send(
                (clone $builder)
                    ->body([$filter => $chunk])
                    ->build('POST')
            );
        }

        return \array_merge(
            SizyaUtils::mapResults(
                $chunks,
                PromiseUtils::settle($promises)->wait(),
                function ($response) use ($filter, $pricesIds) {
                    $dtos   = [];
                    $values = [];

                    foreach ($this->decodeResponse($response)['result']['items'] as $item) {
                        $values[] = (string) $item[$filter];
                        $dtos[]   = $this->_convert($item, $pricesIds);
                    }

                    return [
                        'dtos'   => $dtos,
                        'values' => $values
                    ];
                }
            ),
            $errors
        );
    }

    /**
     * Конвертировать цены товара
     *
     * @param array $item      Цена из Ozon
     * @param array $pricesIds Фильтры по идентификаторам цен
     *
     *
     * @return ProductPricesDTO
     */
    private function _convert(array $item, array $pricesIds): ProductPricesDTO
    {
        return ProductPricesDTO::fromArray([
            'id'      => (string) $item['product_id'],
            'article' => $item['offer_id'],
            'prices'  => [
                PriceDTO::fromArray([
                    'id'       => 'price',
                    'name'     => 'Price',
                    'value'    => (float) $item['price']['price'],
                    'original' => $item['price']
                ]),
                PriceDTO::fromArray([
                    'id'       => 'oldPrice',
                    'name'     => 'Old price',
                    'value'    => (float) $item['price']['old_price'],
                    'original' => $item['price']
                ]),
                PriceDTO::fromArray([
                    'id'       => 'minPrice',
                    'name'     => 'Min price',
                    'value'    => (float) $item['price']['marketing_price'],
                    'original' => $item['price']
                ])
            ],
            'original' => $item
        ]);
    }
}
