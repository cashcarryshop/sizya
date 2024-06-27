<?php
/**
 * Синхронизатор остатков МойСклад->Ozon
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Moysklad\Ozon;

use CashCarryShop\Synchronizer\SynchronizerSourceInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Sizya\Synchronizer\AbstractSynchronizer;
use CashCarryShop\Sizya\Moysklad\Stocks as MoyskladStocks;
use CashCarryShop\Sizya\Ozon\Stocks as OzonStocks;
use CashCarryShop\Sizya\Events\Error;
use CashCarryShop\Sizya\Events\Success;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Respect\Validation\Validator as v;

/**
 * Синхронизатор остатков МойСклад->Ozon
 *
 * @category Moysklad
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
        return $source instanceof MoyskladStocks;
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
        return $target instanceof OzonStocks;
    }

    /**
     * Перехватить ошибку и вызвать событие Error
     *
     * @param PromiseInterface $promise Promise
     *
     * @return PromiseInterface
     */
    protected function eventOtherwise(PromiseInterface $promise): PromiseInterface
    {
        $promise->otherwise(function ($reason) {
            $this->event(new Error($reason));
            $this->running = false;
        });

        return $promise;
    }

    /**
     * Вызвать событие Success на выполнение Promise
     *
     * @param PromiseInterface $promise Promise
     *
     * @return void
     */
    protected function eventSuccess(PromiseInterface $promise): void
    {
        $promise->then(function ($result) {
            $this->running = false;
            $this->event(new Success($result));
        });
    }

    /**
     * Получить корректный ключ для получения
     * количества остатков МойСклад
     *
     * @param array $stock Остаток МойСклад
     *
     * @return int
     */
    protected function getStock(array $stock): int
    {
        return $stock[$this->source->getSettings('stockType')];
    }

    /**
     * Получить соотношения артикулов
     * товаров с их идентификаторами
     *
     * @param array $ids Идентификаторы ассортимента
     *
     * @return PromiseInterface
     */
    protected function getIdArticleRelations(array $ids): PromiseInterface
    {
        $builder = $this->source->builder()->point('entity/assortment');

        $requests = [];
        foreach (array_chunk($ids, 100) as $chunk) {
            $clone = clone $builder;

            foreach ($chunk as $id) {
                $clone->filter('id', $id);
            }

            $requests[] = $clone->build('get');
        }

        $pool = $this->source->pool($requests, 5);
        return $this->eventOtherwise(
            $this->source->getPromiseResolver()->settle($pool->getPromises())->then(
                static function ($results) use ($ids) {
                    $assortment = array_merge(
                        ...array_map(
                            static function ($result) {
                                if ($result['state'] === PromiseInterface::REJECTED) {
                                    return [];
                                }

                                return $result['value']->getBody()->toArray()['rows'];
                            },
                            $results
                        )
                    );

                    return array_combine(
                        array_column($assortment, 'id'),
                        array_map(
                            static fn ($item) => $item['meta']['type'] === 'product'
                                ? $item['article'] ?? 'undefined'
                                : $item['code'] ?? 'undefined',
                            $assortment
                        )
                    );
                }
            )
        );
    }

    /**
     * Собрать данные для обновления остатков
     *
     * @param array $storeRelations     Отношения складов
     * @param array $idArticleRelations Отношения идентификаторов и артикулов товаров
     * @param array $stocks             Остатки МойСклад
     *
     * @return array
     */
    protected function getUpdateData(
        array $storeRelations,
        array $idArticleRelations,
        array $stocks
    ): array {
        $transformedData = [];

        foreach ($stocks as $stock) {
            $storeRelation = null;

            foreach ($storeRelations as $relation) {
                if (in_array($stock['storeId'], $relation['source'])) {
                    $storeRelation = $relation;
                    break;
                }
            }

            if ($storeRelation) {
                $articleRelation = $idArticleRelations[$stock['assortmentId']] ?? null;

                if ($articleRelation) {
                    $key = $articleRelation. '-'. $storeRelation['target'];

                    if (isset($transformedData[$key])) {
                        $transformedData[$key]['stock'] += $this->getStock($stock);
                        continue;
                    }

                    $stock = $this->getStock($stock);
                    if ($stock >= 0) {
                        $transformedData[$key] = [
                            'offer_id' => $articleRelation,
                            'warehouse_id' => $storeRelation['target'],
                            'stock' => $stock
                        ];
                    }
                }
            }
        }

        return array_values($transformedData);
    }

    /**
     * Синхронизировать по складам
     *
     * @param array $relations Отношения складов МойСклад и Ozon
     *
     * @return PromiseInterface
     */
    protected function synchronizeByStores(array $relations): PromiseInterface
    {
        return $this->eventOtherwise(
            $this->source->getShort('bystore')->then(
                function ($results) use ($relations) {
                    $stocks = array_merge(
                        ...array_map(
                            static function ($result) {
                                if ($result['state'] === PromiseInterface::REJECTED) {
                                    return [];
                                }

                                return $result['value']->getBody()->toArray();
                            },
                            $results
                        )
                    );

                    $promise = $this->getIdArticleRelations(
                        array_unique(
                            array_column($stocks, 'assortmentId')
                        )
                    );

                    $this->eventOtherwise(
                        $promise->then(function ($result) use ($stocks, $relations) {
                            $promise = $this->target->updateWarehouse(
                                $this->getUpdateData($relations, $result, $stocks)
                            );

                            $this->eventSuccess($promise);
                            $this->eventOtherwise($promise);
                        })
                    );
                }
            )
        );
    }

    /**
     * Синхронизировать все остатки
     *
     * @return PromiseInterface
     */
    protected function synchronizeAll(): PromiseInterface
    {
        $this->eventOtherwise(
            $this->source->getShort('all')->then(function ($response) {
                $stocks = $response->getBody()->toArray();
                $ids = array_column($stocks, 'assortmentId');

                $this->getIdArticleRelations($ids)->then(
                    function ($relations) use ($stocks) {
                        $promise = $this->target->update(array_map(
                            fn ($stock) => [
                                'offer_id' => $relations[$stock['assortmentId']]
                                    ?? 'undefined',
                                'stock' => $this->getStock($stock)
                            ], $stocks
                        ));

                        $this->eventOtherwise($promise);
                        $this->eventSuccess($promise);
                    }
                );
            })
        );
    }

    /**
     * Синхронизировать
     *
     * @param array $settings Настройки для синхронизации
     *
     * @return bool
     */
    protected function process(array $settings = []): bool
    {
        v::keySet(
            v::key('wait', v::boolType()),
            v::key('relations', v::optional(
                v::each(
                    v::keySet(
                        v::key(
                            'source', v::arrayType()
                                ->each(v::stringType()->length(36))
                        ),
                        v::key('target', v::intType())
                    )
                )
            ))
        )->assert(
            $settings = [
                'wait' => $settings['wait'] ?? false,
                'relations' => $relations = $settings['relations'] ?? [],
            ]
        );

        $promise = $relations
            ? $this->synchronizeByStores($relations)
            : $this->synchronizeAll();

        $settings['wait'] && $promise->wait();

        return true;
    }
}
