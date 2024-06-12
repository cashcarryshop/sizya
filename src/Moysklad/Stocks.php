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

use React\Promise\PromiseInterface;
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
     * Настройки
     *
     * @var array
     */
    public readonly array $settings;

    /**
     * Создание объекта для работы с остатками
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);

        $settings = [
            'stores' => $settings['stores'] ?? [],
            'credentials' => $settings['credentials'] ?? [],
            'assortment' => $settings['assortment'] ?? [],
            'stockType' => $settings['stockType'] ?? 'quantity',
            'changedSince' => $settings['changedSince'] ?? null
        ];

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
        )->assert($settings);

        $this->settings = $settings;
    }

    /**
     * Получить краткий отчет об остатках
     *
     * @param string $method Метод (all, bystore)
     * @param array  $stores Хранилища по которым фильтровать
     *
     * @return PromiseInterface
     */
    protected function _getShort(string $method, array $stores): PromiseInterface
    {
        $builder = $this->builder()
            ->point("report/stock/$method/current")
            ->param('stockType', $this->settings['stockType']);

        if ($this->settings['changedSince']) {
            $builder->param('changedSince', $this->settings['changedSince']);
        }

        foreach (array_splice($stores, 0, min(100, count($stores))) as $store) {
            $builder->filter('storeId', $store);
        }

        $deferred = $this->deferred();

        $this->send($builder->build('GET'))->then(
            function ($response) use ($method, $stores, $deferred) {
                if ($stores) {
                    $stocks = $response->getBody()->toArray();
                    return $this->getShortStocksReport($method, $stores)->then(
                        fn ($response) => $deferred->resolve(
                            $response->withBody(
                                $this->body(
                                    array_merge(
                                        $stocks,
                                        $response->getBody()->toArray()
                                    )
                                )
                            )
                        ),
                        [$deferred, 'reject']
                    );
                }

                $deferred->resolve($response);
            },
            [$deferred, 'reject']
        );

        return $deferred->promise();
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
        return $this->_getShort($method, $this->settings['stores']);
    }
}
