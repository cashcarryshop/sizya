<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Synchronizer;

use CashCarryShop\Synchronizer\SynchronizerSourceInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Sizya\ProductsPricesGetterInterface;
use CashCarryShop\Sizya\ProductsPricesUpdaterInterface;
use CashCarryShop\Sizya\Events\Success;
use CashCarryShop\Sizya\DTO\ProductPricesUpdateDTO;
use CashCarryShop\Sizya\DTO\PriceUpdateDTO;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Синхронизатор цен товаров.
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class ProductsPricesSynchronizer extends AbstractSynchronizer
{
    /**
     * Проверить, поддерживается ли источник
     *
     * @param SynchronizerSourceInterface $source Источник
     *
     * @return bool
     */
    public function supportsSource(SynchronizerSourceInterface $source): bool
    {
        return $source instanceof ProductsPricesGetterInterface;
    }

    /**
     * Проверить, поддерживается ли цель
     *
     * @param SynchronizerTargetInterface $target Цель
     *
     * @return bool
     */
    public function supportsTarget(SynchronizerTargetInterface $target): bool
    {
        return $target instanceof ProductsPricesGetterInterface
            && $target instanceof ProductsPricesUpdaterInterface;
    }

    /**
     * Получить правила валидации настроек
     * для метода synchronize.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'relations' => [
                new Assert\NotBlank,
                new Assert\All(
                    new Assert\Collection([
                        'source' => new Assert\Type('string'),
                        'target' => new Assert\Type('string')
                    ])
                )
            ],
        ];
    }

    /**
     * Синхронизировать.
     *
     * Для более плотного понимания настроек, см. выше
     * метод rules (правила валидации).
     *
     * Массив $settings принимает:
     *
     * - relations: (array) Отношения идентификаторов цен
     *
     * @param array $settings Настройки для синхронизации
     *
     * @return bool
     */
    protected function process(array $settings): bool
    {
        $relations = \array_combine(
            $sPrices = \array_column($settings['relations'], 'source'),
            $tPrices = \array_column($settings['relations'], 'target')
        );

        $sources = $this->source->getProductsPrices($sPrices);

        $articles = \array_column($sources, 'article');
        $targets = $this->target->getProductsPricesByArticles($articles, $tPrices);

        \array_multisort(
            $articles,
            SORT_STRING,
            $sources
        );

        \array_multisort(
            \array_column($targets, 'article'),
            SORT_STRING,
            $targets
        );

        $forUpdate = [];

        \reset($targets);
        foreach ($sources as $source) {
            $target = \current($targets);

            if ($target === false) {
                break;
            }

            if ($target->article === $source->article) {
                $forUpdate[] = ProductPricesUpdateDTO::fromArray([
                    'id'      => $target->id,
                    'article' => $target->article,
                    'prices'  => \array_map(
                        static fn ($price) => PriceUpdateDTO::fromArray([
                            'id'    => $relations[$price->id],
                            'value' => $price->value
                        ]),
                        $source->prices
                    )
                ]);
            }

            \next($targets);
        }

        $this->event(
            new Success([
                'prices' => $this->target->updateProductsPrices($forUpdate)
            ])
        );

        return true;
    }
}
