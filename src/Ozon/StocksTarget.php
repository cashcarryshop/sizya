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

use CashCarryShop\Sizya\StocksUpdaterInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Sizya\DTO\StockUpdateDTO;
use CashCarryShop\Sizya\DTO\StockDTO;
use CashCarryShop\Sizya\DTO\ApiErrorDTO;
use CashCarryShop\Sizya\DTO\ApiErrorsDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\Utils as SizyaUtils;
use GuzzleHttp\Promise\Utils as PromiseUtils;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Элемент для синхронизации остатков Ozon.
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class StocksTarget extends StocksSource
    implements StocksUpdaterInterface, SynchronizerTargetInterface
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
            $firstStepValidated,
            $firstStepErrors
        ] = SizyaUtils::splitByValidationErrors(
            $this->getValidator(), $stocks, [
                new Assert\NotBlank,
                new Assert\Type(StockUpdateDTO::class)
            ]
        );
        unset($stocks);

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

                if ($stock->id === null) {
                    $item['offer_id']   = $key = $stock->article;
                } else {
                    $item['product_id'] = $key = $stock->id;
                }

                return $item;
            },
            fn ($data) => $this->send(
                (clone $builder)
                    ->body(['stocks' => $data])
                    ->build('POST')
            )
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
                        $key = \array_key_exists($answer['product_id'], $chunk)
                            ? $answer['product_id'] : $answer['offer_id'];

                        $item        = $chunk[$key];
                        $chunk[$idx] = $item;
                        unset($chunk[$key]);

                        if ($answer['updated']) {
                            $dtos[] = StockDTO::fromArray([
                                'id'          => (string) $answer['product_id'],
                                'article'     => $answer['offer_id'],
                                'warehouseId' => (string) $answer['warehouse_id'],
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

                    return [
                        'values' => $chunk,
                        'dtos'   => $dtos
                    ];
                }
            ),
            $firstStepErrors,
            $errors
        );
    }
}
