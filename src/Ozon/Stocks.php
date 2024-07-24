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
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Respect\Validation\Validator as v;

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
     * @param array $stocks Остатки
     *
     * @return array
     */
    private function _update(array $stocks): array
    {
        $builder = $this->builder()->point('v2/products/stocks');
        $promises = [];

        $chunks = array_chunk(
            array_map(
                static function ($stock) {
                    $output = [
                        'warehouse_id' => $stock['warehouse_id'],
                        'stock' => $stock['quantity']
                    ];

                    if (isset($stock['id'])) {
                        $output['product_id'] = $stock['id'];
                        return $output;
                    }

                    $output['offer_id'] = $stock['article'];
                    return $output;
                },
                $stocks
            ),
            100
        );
        foreach ($chunks as $chunk) {
            $promises[] = $this->decode(
                $this->send(
                    (clone $builder)
                        ->body(['stocks' => $chunk])
                        ->build('POST')
                )
            );
        }

        $results = Utils::settle($promises)->wait();

        foreach ($results as $idx => $result) {
            $indexes = array_keys(
                array_slice(
                    $stocks,
                    $offset = $idx * 100,
                    100,
                    true
                )
            );

            if ($result['state'] === PromiseInterface::REJECTED) {
                $reason = $result['reason']->getMessage();
                foreach ($indexes as $index) {
                    $stocks[$index]['error'] = true;
                    $stocks[$index]['reason'] = $reason;
                    $stocks[$index]['original'] = $result['reason'];
                }
                continue;
            }

            foreach ($indexes as $index) {
                $answer = $result['value']['result'][$index - $offset];

                $stocks[$index]['original'] = $answer;
                if ($answer['updated']) {
                    $stocks[$index]['error'] = false;
                    continue;
                }

                $stocks[$index]['error'] = true;
                $stocks[$index]['reason'] = $answer['errors'];
            }
        }

        return $stocks;
    }

    /**
     * Обновить остатки по идентификаторам
     *
     * Смотреть `StocksUpdaterInterface::updateStocksByIds`
     *
     * @param array $stocks Остатки
     *
     * @return array
     */
    public function updateStocksByIds(array $stocks): array
    {
        return $this->_update($stocks);
    }

    /**
     * Обновить остатки товаров по артикулам
     *
     * Смотреть `StocksUpdaterInterface::updateStocksByArticles`
     *
     * @param array $stocks Остатки
     *
     * @return array
     */
    public function updateStocksByArticles(array $stocks): array
    {
        return $this->_update($stocks);
    }
}
