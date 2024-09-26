<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya
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
use CashCarryShop\Sizya\StocksGetterInterface;
use CashCarryShop\Sizya\StocksUpdaterInterface;
use CashCarryShop\Sizya\Events\Success;
use Respect\Validation\Validator as v;

/**
 * Синхронизатор остатков
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class StocksSynchronizer extends AbstractSynchronizer
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
        return $source instanceof StocksGetterInterface;
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
        return $target instanceof StocksUpdaterInterface;
    }

    /**
     * Синхронизировать
     *
     * Массив $settings принимает:
     *
     * - optional(default_warehouse): (string) Идентификатор склада по-умолчанию
     * - optional(relations):         (array)  Массив связей между складами
     *
     * Одно из полей `default_warehouse` или `relations` должно
     * быть обязательно передано. Также могут работать вместе.
     *
     * Логика такая:
     *
     * 1. Если из массива `relations` найдены отношения между
     *    складами, то, складывая значения остатков из складов
     *    источников, обновляет их на складе цели.
     *
     * 2. Если отношения складов не найдены, то устанавливает
     *    целью склад из `default_warehouse`, складывая
     *    предыдущие остатки, отношения которых не были найдены,
     *    и обновляет их.
     *
     * 3. Если отношение не найдено и параметр `default_warehouse`
     *    не был передан, пропускает остатки этого склада.
     *
     * О `relations`:
     *
     * - source: (array)  Массив с идентификаторами складов источников
     * - target: (string) Идентификатор склада цели
     *
     * @param array $settings Настройки для синхронизации
     *
     * @return bool
     */
    protected function process(array $settings): bool
    {
        v::allOf(
            v::key('default_warehouse', v::stringType(), false),
            v::key('relations', v::length(1)->each(
                v::keySet(
                    v::key('source', v::each(v::stringType())),
                    v::key('target', v::stringType())
                )
            ), false),
            v::when(
                v::key('default_warehouse'),
                v::alwaysValid(),
                v::key('relations')
            ),
        )->assert($settings);

        $update = [];
        $stocks = $this->source->getStocks();

        $default = $settings['default_warehouse'] ?? false;
        foreach ($stocks as $stock) {
            $target = $default;

            if (isset($settings['relations'])) {
                foreach ($settings['relations'] as $relation) {
                    $sources = $relation['source'];
                    if (in_array($stock['warehouse_id'], $sources)) {
                        $target = $relation['target'];
                        break;
                    }
                }
            }

            if ($target) {
                $key = $stock['article'] . '-' . $target;
                if (isset($update[$key])) {
                    $update[$key]['quantity'] += max(0, $stock['quantity']);
                    continue;
                }

                $update[$key] = [
                    'article' => $stock['article'],
                    'warehouse_id' => $target,
                    'quantity' => max(0, $stock['quantity'])
                ];
            }
        }

        $this->event(
            new Success([
                'stocks' => $this->target->updateStocksByArticles(
                    array_values($update)
                )
            ])
        );

        return true;
    }
}
