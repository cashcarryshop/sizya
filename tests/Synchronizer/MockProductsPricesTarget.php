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

use CashCarryShop\Sizya\DTO\ProductPricesUpdateDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\Utils;
use CashCarryShop\Sizya\ProductsPricesUpdaterInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * Тестовый класс цели синхронизации цен товаров.
 *
 * @category TestMock
 * @package  Sizya
 * @author   TheWhatis <anton-gnogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class MockProductsPricesTarget extends MockProductsPricesSource
    implements SynchronizerTargetInterface,
               ProductsPricesUpdaterInterface
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
        ] = Utils::splitByValidationErrors(
            Validation::createValidatorBuilder()->getValidator(),
            $productsPrices,
            [
                new Assert\NotBlank,
                new Assert\Type(ProductPricesUpdateDTO::class)
            ]
        );
        unset($productsPrices);

        [
            $validated,
            $errors
        ] = Utils::splitByValidationErrors(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator(),
            $firstStepValidated,
            [new Assert\Valid]
        );
        unset($firstStepValidated);

        $ids      = \array_column($this->settings['items'], 'id');
        $articles = \array_column($this->settings['items'], 'article');

        $productsPrices = [];

        foreach ($validated as $forUpdate) {
            if ($forUpdate->id) {
                $idx = \array_search($forUpdate->id, $ids);
            } else {
                $idx = \array_search($forUpdate->article, $articles);
            }

            if ($idx === false) {
                $productsPrices[] = ByErrorDTO::fromArray([
                    'type'  => ByErrorDTO::NOT_FOUND,
                    'value' => $forUpdate
                ]);
                continue;
            }

            $item = $this->settings['items'][$idx];

            foreach ($item->prices as $price) {
                foreach ($forUpdate->prices as $updatePrice) {
                    if ($price->id === $updatePrice->id) {
                        $price->value = $updatePrice->value;
                        continue 2;
                    }
                }
            }

            $productsPrices[] = $item;
        }

        return \array_merge($productsPrices, $firstStepErrors, $errors);
    }
}
