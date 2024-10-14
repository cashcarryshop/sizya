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

use CashCarryShop\Sizya\ProductsPricesUpdaterInterface;
use CashCarryShop\Sizya\DTO\ProductPricesDTO;
use CashCarryShop\Sizya\DTO\ProductPricesUpdateDTO;
use CashCarryShop\Sizya\DTO\PriceDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\DTO\ApiErrorsDTO;
use CashCarryShop\Sizya\DTO\ApiErrorDTO;
use CashCarryShop\Sizya\Utils as SizyaUtils;
use GuzzleHttp\Promise\Utils as PromiseUtils;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Класс для работы с ценами товаров Ozon.
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see ProductsPricesUpdaterInterface
 */
class ProductsPricesTarget extends ProductsPricesSource
    implements ProductsPricesUpdaterInterface
{
    /**
     * Обновить цены товаров.
     *
     * @param ProductPricesUpdateDTO[] $productsPrices Цены товаров
     *
     * @return array<int, ProductPricesDTO|ByErrorDTO>
     */
    public function updateProductsPrices(array $productsPrices): array
    {
        [
            $firstStepValidated,
            $firstStepErrors
        ] = SizyaUtils::splitByValidationErrors(
            $this->getValidator(), $productsPrices, [
                new Assert\NotBlank,
                new Assert\Type(ProductPricesUpdateDTO::class),
            ]
        );
        unset($productsPrices);

        [
            $validated,
            $errors
        ] = SizyaUtils::splitByValidationErrors(
            $this->getValidator(),
            $firstStepValidated,
            [new Assert\Valid]
        );

        if (\count($validated) === 0) {
            return \array_merge($firstStepErrors, $errors);
        }

        $builder = $this->builder()->point('v1/product/import/prices');

        [
            'chunks'   => $chunks,
            'promises' => $promises
        ] = SizyaUtils::getByChunks(
            $validated,
            static function ($productPrices, &$key) {
                $item = [];

                if (null === $productPrices->id) {
                    $item['offer_id']   = $key = $productPrices->article;
                } else {
                    $item['product_id'] = $key = $productPrices->id;
                }

                foreach ($productPrices->prices as $price) {
                    if ($price->id === 'price') {
                        $item['price'] = (string) $price->value;
                        continue;
                    }

                    if ($price->id === 'oldPrice') {
                        $item['old_price'] = (string) $price->value;
                        continue;
                    }

                    if ($price->id === 'minPrice') {
                        $item['min_price'] = (string) $price->value;
                    }
                }

                return $item;
            },
            fn ($data) => $this->send(
                (clone $builder)
                    ->body(['prices' => $data])
                    ->build('POST')
            )
        );

        $getApiError = static fn ($error) =>
            ApiErrorDTO::fromArray([
                'message'  => $error['message'],
                'original' => $error
            ]);

        $priceNamesMap = [
            'price'    => 'Price',
            'minPrice' => 'Min price',
            'oldPrice' => 'Old price'
        ];

        $productsPrices = SizyaUtils::mapResults(
            $chunks,
            PromiseUtils::settle($promises)->wait(),
            function ($response, &$chunk) use ($getApiError, $priceNamesMap) {
                $dtos = [];

                $answers = $this->decodeResponse($response)['result'];
                foreach ($answers as $idx => $answer) {
                    $key = \array_key_exists($answer['product_id'], $chunk)
                        ? $answer['product_id'] : $answer['offer_id'];

                    $item        = $chunk[$key];
                    $chunk[$idx] = $item;
                    unset($chunk[$key]);

                    if ($answer['updated']) {
                        $dtos[] = ProductPricesDTO::fromArray([
                            'id'       => (string) $answer['product_id'],
                            'article'  => $answer['offer_id'],
                            'prices'   => \array_filter(
                                \array_map(
                                    static fn ($price) => PriceDTO::fromArray([
                                        'id'    => $price->id,
                                        'name'  => $priceNamesMap[$price->id] ?? null,
                                        'value' => $price->value

                                    ]),
                                    $item->prices
                                ),
                                static fn ($price) => null !== $price->name
                            ),
                            'original' => [
                                'from'   => $item,
                                'answer' => $answer
                            ]
                        ]);

                        continue;
                    }

                    $dtos[] = ByErrorDTO::fromArray([
                        'type'   => ByErrorDTO::API,
                        'value'  => $item,
                        'reason' => new ApiErrorsDTO(
                            \array_map($getApiError, $answer['errors'])
                        )
                    ]);
                }

                return [
                    'values' => $chunk,
                    'dtos'   => $dtos
                ];
            }
        );

        return \array_merge($productsPrices, $firstStepErrors, $errors);
    }
}
