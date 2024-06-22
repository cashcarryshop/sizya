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
class Stocks extends AbstractEntity
{
    /**
     * Создание объекта для работы с остатками
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        parent::__construct(
            array_merge(
                $settings, [
                    'stores' => $settings['stores'] ?? [],
                    'credentials' => $settings['credentials'] ?? [],
                    'assortment' => $settings['assortment'] ?? [],
                    'stockType' => $settings['stockType'] ?? 'quantity',
                    'changedSince' => $settings['changedSince'] ?? null
                ]
            )
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
     * Получить краткий отчет об остатках
     *
     * @param string $method Метод (all, bystore)
     * @param array  $stores Хранилища по которым фильтровать
     *
     * @return PromiseInterface
     */
    private function _getShort(string $method, array $stores): PromiseInterface
    {
        $builder = $this->builder()
            ->point("report/stock/$method/current")
            ->param('stockType', $this->getSettings('stockType'));

        if ($this->getSettings('changedSince')) {
            $builder->param('changedSince', $this->getSettings('changedSince'));
        }

        foreach (array_splice($stores, 0, min(100, count($stores))) as $store) {
            $builder->filter('storeId', $store);
        }

        $promise = $this->promise();

        $this->send($builder->build('GET'))->then(
            function ($response) use ($method, $stores, $promise) {
                if ($stores) {
                    $stocks = $response->getBody()->toArray();
                    return $this->_getShort($method, $stores)->then(
                        fn ($response) => $promise->resolve(
                            $response->withBody(
                                $this->body(
                                    array_merge(
                                        $stocks,
                                        $response->getBody()->toArray()
                                    )
                                )
                            )
                        ),
                        [$promise, 'reject']
                    );
                }

                $promise->resolve($response);
            },
            [$promise, 'reject']
        )->otherwise([$promise, 'reject']);

        return $promise;
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
        return $this->_getShort($method, $this->getSettings('stores'));
    }
}
