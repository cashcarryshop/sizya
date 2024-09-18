<?php
/**
 * Элемент для синхронизации остатков Ozon
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

use CashCarryShop\Sizya\StocksUpdaterInterface;
use CashCarryShop\Sizya\DTO\StockUpdateDTO;
use CashCarryShop\Sizya\DTO\StockDTO;
use CashCarryShop\Sizya\DTO\ApiErrorDTO;
use CashCarryShop\Sizya\DTO\ApiErrorsDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\Utils as SizyaUtils;
use GuzzleHttp\Promise\Utils as PromiseUtils;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Элемент для синхронизации остатков Ozon
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class Stocks extends AbstractTarget implements StocksUpdaterInterface
{
    /**
     * Обновить остатки
     *
     * @param StockUpdateDTO[] $stocks Остатки
     *
     * @return array<int, StockDTO|ByErrorDTO>
     */
    public function updateStocks(array $stocks): array
    {
        [
            $errors,
            $validated
        ] = SizyaUtils::splitByValidationErrors(
            $this->getValidator(), $stocks, [
                new Assert\NotBlank,
                new Assert\Type(StockUpdateDTO::class),
                new Assert\Valid
            ]
        );
        unset($stocks);

        $builder = $this->builder()->point('v2/products/stocks');

        [
            'chunks'   => $chunks,
            'promises' => $promises
        ] = SizyaUtils::getByChunks(
            $validated,
            static function ($stock, &$key) {
                $item = [
                    'warehouse_id' => $stock->warehouseId,
                    'stock'        => $stock->quantity
                ];

                if (\is_null($stock->id)) {
                    $item['offer_id']   = $key = $stock->article;
                } else {
                    $item['product_id'] = $key = $stock->id;
                }

                return $item;
            },
            static fn ($data) => (clone $builder)
                ->body(['stocks' => $data])
                ->build('POST')
        );

        $getApiError = static fn ($error) =>
            ApiErrorDTO::fromArray([
                'message'  => $error['error'],
                'original' => $error,
            ]);

        return \array_merge(
            SizyaUtils::mapResults(
                $chunks,
                PromiseUtils::settle($promises)->wait(),
                function ($response, &$chunk) use ($getApiError) {
                    $dtos   = [];

                    foreach ($this->decodeResponse($response)['result'] as $idx => $answer) {
                        $key = \array_key_exists($chunk, $answer['product_id'])
                            ? $answer['product_id'] : $answer['offer_id'];

                        $item        = $chunk[$key];
                        $chunk[$idx] = $item;
                        unset($chunk[$key]);

                        if ($answer['updated']) {
                            $dtos[] = StockDTO::fromArray([
                                'id'          => $answer['product_id'],
                                'article'     => $answer['offer_id'],
                                'warehouseId' => $answer['warehouse_id'],
                                'quantity'    => $item->quantity,
                                'original'    => $answer
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

                    return [$dtos, $chunk];
                }
            ),
            $errors
        );
    }
}
