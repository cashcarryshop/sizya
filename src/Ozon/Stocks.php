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
class Stocks extends AbstractEntity
{
    /**
     * Стандартная валидация остатков
     *
     * @param array $stocks Остатки
     *
     * @return void
     */
    protected function _validateStocks(array $stocks): void
    {
        v::each(
            v::allOf(
                v::when(
                    v::key('offer_id'),
                    v::key('offer_id', v::stringType()),
                    v::key('product_id', v::intType())
                ),
                v::key('stock', v::intType()->min(0))
            )
        )->assert($stocks);
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
        $this->_validateStocks($stocks);

        $builder = $this->builder()
            ->point('v1/product/import/stocks')
            ->body(['stocks' => $stocks]);

        $promise = $this->promise();

        $this->send($builder->build('POST'))->then(
            function ($response) use ($stocks, $promise) {
                if ($stocks) {
                    $result = $response->getBody()->toArray()['result'];
                    return $this->update($stocks)->then(
                        static function ($response) use ($result, $promise) {
                            return $promise->resolve(
                                $response->withBody(
                                    Utils::getJsonBody([
                                        'result' => array_merge(
                                            $result,
                                            $response->getBody()->toArray()['result']
                                        )
                                    ])
                                )
                            );
                        },
                        [$promise, 'reject']
                    );
                }

                $promise->resolve($response);
            },
            [$promise, 'reject']
        );

        return $promise;
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
        $this->_validateStocks($stocks);
        v::each(v::key('warehouse_id', v::intType()))->assert($stocks);

        $promise = $this->promise();

        $builder = $this->builder()
            ->point('v2/products/stocks')
            ->body(['stocks' => array_splice($stocks, 0, min(100, count($stocks)))]);

        $this->send($builder->build('POST'))->then(
            function ($response) use ($stocks, $promise) {
                if ($stocks) {
                    $result = $response->getBody()->toArray()['result'];
                    return $this->updateWarehouse($stocks)->then(
                        static function ($response) use ($result, $promise) {
                            return $promise->resolve(
                                $response->withBody(
                                    Utils::getJsonBody([
                                        'result' => array_merge(
                                            $result,
                                            $response->getBody()->toArray()['result']
                                        )
                                    ])
                                )
                            );
                        },
                        [$promise, 'reject']
                    );
                }

                $promise->resolve($response);
            },
            [$promise, 'reject']
        );

        return $promise;
    }
}
