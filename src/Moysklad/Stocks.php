<?php
/**
 * Класс остатков
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Moysklad;

use CashCarryShop\Sizya\Http\Utils;
use GuzzleHttp\Promise\PromiseInterface;
use Respect\Validation\Validator as v;

/**
 * Класс с настройками и логикой получения
 * остатков Moysklad
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
final class Stocks extends AbstractEntity
{
    /**
     * Иницилизировать объект
     *
     * @param array $settings Настройки
     *
     * @return void
     */
    protected function init(array $settings): void
    {
        $this->settings = array_merge(
            $settings, [
                'stores' => $settings['stores'] ?? [],
                'credentials' => $settings['credentials'] ?? [],
                'assortment' => $settings['assortment'] ?? [],
                'stockType' => $settings['stockType'] ?? 'quantity',
                'changedSince' => $settings['changedSince'] ?? null
            ]
        );

        v::keySet(
            v::key('credentials', v::alwaysValid()),
            v::key('stores', v::allOf(
                v::arrayType(),
                v::when(
                    v::notEmpty(),
                    v::each(
                        v::stringType(),
                        v::length(36)
                    ),
                    v::alwaysValid()
                )
            )),
            v::key('assortment', v::allOf(
                v::arrayType(),
                v::when(
                    v::notEmpty(),
                    v::each(
                        v::stringType(),
                        v::length(36)
                    ),
                    v::alwaysValid()
                )
            )),
            v::key('stockType', v::in([
                'stock',
                'freeStock',
                'quantity',
                'reserve',
                'inTransit'
            ])),
            v::key('changedSince', v::optional(v::dateTime('Y-m-d H:i:s')))
        )->assert($this->settings);
    }

    /**
     * Получить короткий отчет об остатках
     *
     * @param string $method Метод (all, bystore)
     *
     * @return PromiseInterface
     */
    public function getShort(string $method = 'all'): PromiseInterface
    {
        v::in(['all', 'bystore'])->assert($method);

        $builder = $this->builder()
            ->point("report/stock/$method/current")
            ->param('stockType', $this->getSettings('stockType'));

        if ($this->getSettings('changedSince')) {
            $builder->param('changedSince', $this->getSettings('changedSince'));
        }

        $promises = [];
        if ($stores = $this->getSettings('stores')) {
            foreach (array_chunk($stores, 100) as $chunk) {
                $clone = clone $builder;

                foreach ($chunk as $store) {
                    $clone->filter('storeId', $store);
                }

                $promises[] = $this->pool()->add($clone->build('GET'));
            }
        }

        if (!$promises) {
            $promises[] = $this->pool()->add($builder->build('GET'));
        }

        return $this->getPromiseAggregator()->settle($promises);
    }
}
