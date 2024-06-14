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

use CashCarryShop\Synchronizer\AbstractSynchronizer;
use CashCarryShop\Synchronizer\SynchronizerSourceInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Sizya\Moysklad\Stocks as MoyskladStocks;
use CashCarryShop\Sizya\Ozon\Stocks as OzonStocks;
use CashCarryShop\Sizya\Events\Error;
use CashCarryShop\Sizya\Events\Success;
use GuzzleHttp\Promise\PromiseInterface;
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
     * @return void
     */
    protected function eventOtherwise(PromiseInterface $promise): void
    {
        $promise->otherwise(fn ($exception) => $this->event(new Error($exception)));
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
        return $stock[$this->source->settings['stockType']];
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

        foreach (array_splice($ids, 0, min(100, count($ids))) as $id) {
            $builder->filter('id', $id);
        }

        $promise = $this->source->promise();

        $this->eventOtherwise(
            $sendPromise = $this->source->send($builder->build('GET'))
        );

        $new = $sendPromise->then(
            function ($response) use ($ids, $promise) {
                $assortment = $response->getBody()->toArray()['rows'];
                $relations = array_combine(
                    array_column($assortment, 'id'),
                    array_map(
                        fn ($item) => $item['meta']['type'] === 'product'
                            ? $item['article'] ?? 'undefined'
                            : $item['code'] ?? 'undefined',
                        $assortment
                    )
                );

                // Вызываем рекурсивно getIdArticleRelations метод, если
                // переданных идентификаторов больше 100
                return $ids
                    ? $this->getIdArticleRelations($ids)->then(
                        function ($response) use ($relations, $promise) {
                            $promise->resolve(
                                $response->withBody(
                                    $this->source->body(
                                        array_merge(
                                            $relations,
                                            $response->getBody()->toArray()
                                        )
                                    )
                                )
                            );
                        }, [$promise, 'reject']
                    )
                    : $promise->resolve(
                        $response->withBody(
                            $this->source->body($relations)
                        )
                    );
            }, [$promise, 'reject']
        );
        $this->eventOtherwise($new);

        return $promise;
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

                    $transformedData[$key] = [
                        'offer_id' => $articleRelation,
                        'warehouse_id' => $storeRelation['target'],
                        'stock' => $this->getStock($stock)
                    ];
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
     * @return bool
     */
    protected function synchronizeByStores(array $relations): bool
    {
        $this->eventOtherwise($promise = $this->source->getShort('bystore'));
        $this->eventOtherwise(
            $promise->then(function ($response) use ($relations) {
                $stocks = $response->getBody()->toArray();
                $promise = $this->getIdArticleRelations(
                    array_unique(
                        array_column($stocks, 'assortmentId')
                    )
                );

                $this->eventOtherwise(
                    $promise->then(function ($response) use ($stocks, $relations) {
                        $this->eventOtherwise(
                            $promise = $this->target->updateWarehouse(
                                $this->getUpdateData(
                                    $relations,
                                    $response->getBody()->toArray(),
                                    $stocks
                                )
                            )
                        );

                        $this->eventOtherwise(
                            $promise->then(fn ($response) => $this->event(
                                new Success($response->getBody()->toArray())
                            ))
                        );
                    })
                );
            })
        );

        return true;
    }

    /**
     * Синхронизировать все остатки
     *
     * @return bool
     */
    protected function synchronizeAll(): bool
    {
        $this->eventOtherwise($promise = $this->source->getShort('all'));
        $this->eventOtherwise(
            $promise->then(function ($response) {
                $stocks = $response->getBody()->toArray();
                $this->getIdArticleRelations(array_column($stocks, 'assortmentId'))->then(
                    function ($response) use ($stocks) {
                        $relations = $response->getBody()->toArray();
                        $this->eventOtherwise(
                            $promise = $this->target->update(array_map(
                                fn ($stock) => [
                                    'offer_id' => $relations[$stock['assortmentId']]
                                        ?? 'undefined',
                                    'stock' => $this->getStock($stock)
                                ], $stocks
                            ))
                        );

                        $this->eventOtherwise(
                            $promise->then(fn ($response) => $this->event(
                                new Success($response->getBody()->toArray())
                            ))
                        );
                    }
                );
            })
        );

        return true;
    }

    /**
     * Синхронизировать
     *
     * @param array $settings Настройки для синхронизации
     *
     * @return bool
     */
    public function synchronize(array $settings = []): bool
    {
        $settings = [
            'relations' => $relations = $settings['relations'] ?? []
        ];

        v::keySet(
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
        )->assert($settings);

        if ($relations) {
            return $this->synchronizeByStores($relations);
        }

        return $this->synchronizeAll();
    }
}
{}
