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

use CashCarryShop\Sizya\StocksUpdaterInterface;
use CashCarryShop\Sizya\Utils;
use CashCarryShop\Sizya\DTO\StockDTO;
use CashCarryShop\Sizya\DTO\StockUpdateDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * Тестовый класс цели синхронизации остатков.
 *
 * @category TestMock
 * @package  Sizya
 * @author   TheWhatis <anton-gnogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class MockStocksTarget extends MockStocksSource
    implements SynchronizerTargetInterface, StocksUpdaterInterface
{

    /**
     * Обновить остатки товаров по идентификаторам
     *
     * Количество возвращаемых элементов должно
     * соответствовать переданным.
     *
     * @param StockUpdateDTO[] $stocks Остатки
     *
     * @see StockUpdateDTO
     * @see StockDTO
     * @see ByErrorDTO
     *
     * @return array<int, StockDTO|ByErrorDTO>
     */
    public function updateStocks(array $stocks): array
    {
        [
            $firstStepValidated,
            $firstStepErrors
        ] = Utils::splitByValidationErrors(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator(),
            $stocks,
            [
                new Assert\NotBlank,
                new Assert\Type(StockUpdateDTO::class)
            ]

        );
        unset($stocks);

        [
            $validated,
            $errors
        ] = Utils::splitByValidationErrors(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator(),
            $firstStepValidated,
            [
                new Assert\Valid
            ]
        );
        unset($firstStepValidated);

        $items = \array_map(
            static fn ($stock, $key) => [
                'key'   => $key,
                'stock' => $stock
            ],
            $this->settings['items'],
            \array_keys($this->settings['items'])
        );

        $byIds = \array_combine(
            \array_map(
                static fn ($stock) => $stock->id . $stock->warehouseId,
                $this->settings['items']
            ),
            $items
        );

        $byArticles = \array_combine(
            \array_map(
                static fn ($stock) => $stock->article . $stock->warehouseId,
                $this->settings['items']
            ),
            $items
        );

        $items = [];
        foreach ($validated as $stock) {
            if ($stock->id) {
                $item = $byIds[$stock->id . $stock->warehouseId] ?? false;
            } else {
                $item = $byArticles[$stock->article . $stock->warehouseId] ?? false;
            }

            if ($item === false) {
                $items[] = ByErrorDTO::fromArray([
                    'type'  => ByErrorDTO::NOT_FOUND,
                    'value' => $stock
                ]);

                continue;
            }

            $data             = $item['stock']->toArray();
            $data['quantity'] = $stock->quantity;
            $data['original'] = [
                'previous' => $item,
                'new'      => $stock
            ];

            $items[]
                = $this->settings['items'][$item['key']]
                = StockDTO::fromArray($data);
        }

        return \array_merge($items, $firstStepErrors, $errors);
    }
}
