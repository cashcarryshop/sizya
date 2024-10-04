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

        $byIds      = [];
        $byArticles = [];

        foreach ($validated as $stock) {
            if ($stock->id) {
                $byIds = $stock;
                continue;
            }

            $byArticles = $stock;
        }

        $items = [];
        \array_multisort(
            \array_column($this->settings['items'], 'article'),
            SORT_STRING,
            $this->settings['items']
        );

        \array_multisort(
            \array_column($byArticles, 'article'),
            SORT_STRING,
            $byArticles
        );

        \reset($this->settings['items']);
        foreach ($byArticles as $stock) {
            $current = \current($this->settings['items']);
            if ($stock->article === $current?->article) {
                $current->quantity = $stock->quantity;
                $items[] = $current;
                continue;
            }

            $items[] = ByErrorDTO::fromArray([
                'type'  => ByErrorDTO::NOT_FOUND,
                'value' => $stock
            ]);
        }


        \array_multisort(
            \array_column($this->settings['items'], 'id'),
            SORT_STRING,
            $this->settings['items']
        );

        \array_multisort(
            \array_column($byIds, 'id'),
            SORT_STRING,
            $byIds
        );

        \reset($this->settings['items']);
        foreach ($byIds as $stock) {
            $current = \current($this->settings['items']);
            if ($stock->id === $current?->id) {
                $current->quantity = $stock->quantity;
                $items[] = $current;
                \next($current);
                continue;
            }

            $items[] = ByErrorDTO::fromArray([
                'type'  => ByErrorDTO::NOT_FOUND,
                'value' => $stock
            ]);
        }

        return \array_merge($items, $errors);
    }
}
