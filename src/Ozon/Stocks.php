<?php
/**
 * Класс остатков
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

use GuzzleHttp\Promise\PromiseInterface;
use Respect\Validation\Validator as v;
use CashCarryShop\Sizya\Http\Utils;

/**
 * Класс с настройками и логикой получения
 * остатков Ozon
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
final class Stocks extends AbstractEntity
{
    /**
     * Обновление остатков
     *
     * @param object $builder Строитель запросов
     * @param array  $stocks  Остатки
     *
     * @return PromiseInterface
     */
    private function _update(object $builder, array $stocks): PromiseInterface
    {
        v::length(1)->each(
            v::allOf(
                v::when(
                    v::key('offer_id'),
                    v::key('offer_id', v::stringType()),
                    v::key('product_id', v::intType())
                ),
                v::key('stock', v::intType()->min(0))
            )
        )->assert($stocks);

        $promises = [];
        if ($chunks = array_chunk($stocks, 100)) {
            foreach ($chunks as $chunk) {
                $promises[] = $this->getPool('stocks')->add(
                    (clone $builder)
                        ->body(['stocks' => $chunk])
                        ->build('POST')
                );
            }
        }

        return $this->getPromiseAggregator()->settle($promises);
    }

    /**
     * Обновить остатки товаров
     *
     * @param array $stocks Остатки
     *
     * @return PromiseInterface
     */
    public function update(array $stocks): PromiseInterface
    {
        return $this->_update(
            $this->builder()->point('v1/product/import/stocks'), $stocks
        );
    }

    /**
     * Обновить остатки товаров по складам
     *
     * @param array $stocks Остатки
     *
     * @return PromiseInterface
     */
    public function updateWarehouse(array $stocks): PromiseInterface
    {
        v::each(v::key('warehouse_id', v::intType()))->assert($stocks);
        return $this->_update(
            $this->builder()->point('v2/products/stocks'), $stocks
        );
    }
}
