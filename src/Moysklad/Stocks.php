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
     */
    public function __construct(array $settings)
    {
        parent::__construct(array_replace(['stockType' => 'quantity'], $settings));

        v::key('stores', v::each(v::stringType()->length(36, 36)), false)
            ->key('assortment', v::each(v::stringType()->length(36, 36)), false)
            ->key('changedSince', v::dateTime('Y-m-d H:i:s'), false)
            ->key('stockType', v::in([
                'stock',
                'freeStock',
                'quantity',
                'reserve',
                'inTransit'
            ]))
            ->assert($this->settings);
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
