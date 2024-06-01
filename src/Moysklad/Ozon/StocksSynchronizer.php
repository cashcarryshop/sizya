<?php
/**
 * Синхронизация остатков МойСклад->Ozon
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Moysklad\Ozon;

use Evgeek\Moysklad\Api\Query\QueryBuilder;
use Respect\Validation\Validator as v;

use CashCarryShop\Synchronizer\SynchronizerSourceInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Synchronizer\AbstractSynchronizer;
use CashCarryShop\Promise\PromiseInterface;

use CashCarryShop\Sizya\Moysklad\Stocks as MoyskladStocks;
use CashCarryShop\Sizya\Ozon\Stocks as OzonStocks;

use CashCarryShop\Sizya\Promise\InteractsWithDeferred;
use CashCarryShop\Sizya\Events\Syncs\ErrorReceivingArticlesByIds;
use CashCarryShop\Sizya\Events\Moysklad\ErrorReceivingShortStocks;
use CashCarryShop\Sizya\Events\Moysklad\ShortStocksReceiving;
use CashCarryShop\Sizya\Events\Moysklad\ShortStocksReceived;
use CashCarryShop\Sizya\Events\Syncs\ReceivingArticlesByIds;
use CashCarryShop\Sizya\Events\Syncs\ReceivedArticlesByIds;
use CashCarryShop\Sizya\Events\Ozon\ErrorUpdatingStocks;
use CashCarryShop\Sizya\Events\Ozon\StocksUpdating;
use CashCarryShop\Sizya\Events\Ozon\StocksUpdated;
use CashCarryShop\Sizya\Events\Error;
use Throwable;

/**
 * Класс синхронизатора остатков Moysklad и Ozon
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/bcashcarryshop/Sizya
 */
class StocksSynchronizer extends AbstractSynchronizer
{
    use InteractsWithDeferred;

    /**
     * Проверить поддерживается ли источник
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
     * Проверить поддерживается ли цель
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
     * Получить артикулы товаров по идентификаторам
     * ассортимента (assortmentId)
     *
     * @param array $ids Идентификаторы
     *
     * @return PromiseInterface
     */
    protected function getArticlesByIds(array $ids): PromiseInterface
    {
        return $this->resolveThrow(function ($promise) use ($ids) {
            $query = $this->source->query()->entity()->assortment();

            foreach (array_splice($ids, 0, min(100, count($ids))) as $id) {
                $query->filter('id', $id);
            }

            $response = array_map(
                fn ($item) => [
                    'id' => $item->id,
                    'article' => $item->meta->type === 'variant'
                        ? $item->code ?? 'undefined'
                        : $item->article ?? 'undefined'
                ],
                $query->get()->rows,
            );


            $response = array_combine(
                array_column($response, 'id'),
                array_column($response, 'article')
            );

            if ($ids) {
                return $this->getArticlesByIds($ids)->then(
                    fn ($nextResponse) => $promise->resolve(
                        array_merge($response, $nextResponse)
                    ),
                    fn ($exception) => $promise->reject($exception)
                );
            }

            $promise->resolve($response);
        });
    }

    /**
     * Собрать массив остатков по складам целей
     *
     * @param array $relations Отношения складов источников и целей
     * @param array $stocks    Полученные остатки
     *
     * @return array
     */
    protected function buildByWarehouses(array $relations, array $stocks): array
    {
        $byWarehouses = [];
        foreach ($relations as $index => $relation) {
            $byWarehouses[$relation['target']] = array_merge(
                ...array_map(
                    fn ($storeId) => array_map(
                        fn ($index) => (array) $stocks[$index],
                        array_keys(
                            array_column($stocks, 'storeId'),
                            $storeId
                        )
                    ),
                    $relation['source']
                )
            );
        }

        foreach ($byWarehouses as $warehouse => $stocks) {
            $ids = array_column($byWarehouses[$warehouse], 'assortmentId');
            $byWarehouses[$warehouse] = [];
            foreach ($stocks as $stock) {
                $byWarehouses[$warehouse][] = array_reduce(
                    array_map(
                        fn ($index) => $stocks[$index],
                        array_keys($ids, $stock['assortmentId'])
                    ),
                    function ($carry, $item) {
                        if ($carry) {
                            $carry['quantity'] += $item['quantity'] ?? 0;
                            return $carry;
                        }

                        return $item;
                    }
                );
            }

            foreach ($byWarehouses[$warehouse] as $item) {
                $found = array_keys($ids, $item['assortmentId']);
                foreach ($found as $index => $unset) {
                    if ($index) {
                        unset($byWarehouses[$warehouse][$unset]);
                    }
                }
            }
        }

        return $byWarehouses;
    }

    /**
     * Собрать корректный остатки
     *
     * @param array $byWarehouses Данные из метода buildByWarehouses
     * @param array $relations    Данные из метода getArticlesByIds
     *
     * @return array
     */
    protected function buildStocks(array $byWarehouses, array $relations): array
    {
        $stocks = array_merge(
            ...array_map(
                fn ($warehouse, $stocks) => array_map(
                    fn ($stock) => [
                        'offer_id' => $relations[$stock['assortmentId']] ?? 'unknown',
                        'stock' => max(0, (int) $stock['quantity']),
                        'warehouse_id' => $warehouse
                    ],
                    $stocks
                ),
                array_keys($byWarehouses),
                array_values($byWarehouses)
            )
        );

        return $stocks;
    }

    /**
     * Распознать и определить каким образом
     * получить остатки
     *
     * @param array $relations Отношения складов источников и цели
     *
     * @return PromiseInterface
     */
    protected function getStocks(array $relations): PromiseInterface
    {
        if ($relations) {
            return $this->source->getShortByStore(
                array_unique(
                    array_merge(
                        ...array_map(
                            fn ($relation) => $relation['source'],
                            $relations
                        )
                    )
                )
            );
        }

        return $this->source->getShortAll();
    }

    /**
     * Вызвать событие, если во время обработки
     * Promise на Resolve произошла ошибка
     *
     * @param PromiseInterface $promise Promise
     *
     * @return void
     */
    protected function eventCatch(PromiseInterface $promise): void
    {
        $promise->catch(function ($exception) {
            $this->event(new Error($exception));
        });
    }

    /**
     * Установить обработчики Promise. в которых будут
     * вызываться события по получению артикулов по
     * идентификаторам
     *
     * @param array            $ids     Идентификаторы
     * @param PromiseInterface $promise Promise
     *
     * @return PromiseInterface
     */
    protected function eventArticles(array $ids, PromiseInterface $promise): void
    {
        $this->eventCatch($promise);
        $promise->then(
            function ($relations) {
                $this->event(new ReceivedArticlesByIds($relations));
            },
            function ($exception) use ($ids) {
                $this->event(new ErrorReceivingArticlesByIds($ids, $exception));
            }
        );
    }

    /**
     * Установить обработички Promise, в которых
     * будут вызываться события, для обновления
     * остатков
     *
     * @param PromiseInterface $promise Promise
     *
     * @return PromiseInterface
     */
    protected function eventUpdating(PromiseInterface $promise): void
    {
        $this->eventCatch($promise);
        $promise->then(
            function ($response) {
                $this->event(new StocksUpdated($response));
            },
            function ($exception) {
                $this->event(new ErrorUpdatingStocks($exception));
            }
        );
    }

    /**
     * Синхронизировать, если отношение и склад 1 к 1
     *
     * @param PromiseInterface $promise Promise на получение артикулов
     * @param array            $stocks  Полученные остатки
     * @param string           $target  Идентификатор целевого склада
     *
     * @return void
     */
    protected function syncOneToOne(
        PromiseInterface $promise,
        array $stocks,
        string $target
    ): void {
        $this->eventCatch($promise);
        $promise->then(
            function ($relations) use ($stocks, $target) {
                $stocks = array_map(
                    fn ($stock) => [
                        'warehouse_id' => (int) $target,
                        'offer_id' => $relations[$stock->assortmentId] ?? 'undefined',
                        'stock' => max(0, (int) $stock->quantity)
                    ], $stocks
                );

                $this->event(new StocksUpdating($stocks));
                $this->eventUpdating($this->target->updateWarehouse($stocks));
            }
        );
    }

    /**
     * Синхронизировать по складам, по-стандарту
     *
     * @param PromiseInterface $promise   Promise на получение артикулов
     * @param array            $stocks    Полученные остатки
     * @param array            $relations Отношения складов
     *
     * @return void
     */
    protected function syncWithStores(
        PromiseInterface $promise,
        array $stocks,
        array $relations
    ): void {
        $byWarehouses = $this->buildByWarehouses($relations, $stocks);

        $this->eventCatch($promise);
        $promise->then(
            function ($relations) use ($byWarehouses) {
                $this->event(
                    new StocksUpdating(
                        $stocks = $this->buildStocks(
                            $byWarehouses,
                            $relations
                        )
                    )
                );

                // var_dump($stocks);
                // exit(1);
                $this->eventUpdating($this->target->updateWarehouse($stocks));
            }
        );
    }

    /**
     * Синхронизировать без складов
     *
     * @param PromiseInterface $promise Promise на получение артикулов
     * @param array            $stocks  Полученные остатки
     *
     * @return void
     */
    protected function syncWithoutStores(
        PromiseInterface $promise,
        array $stocks
    ): void {
        $this->eventCatch($promise);
        $promise->then(
            function ($relations) use ($stocks) {
                $this->event(new StocksUpdating(
                    $stocks = array_map(
                        fn ($stock) => [
                            'offer_id' => $relations[$stock->assortmentId] ?? 'unknown',
                            'stock' => max(0, (int) $stock->quantity)
                        ], $stocks
                    ))
                );

                $this->eventUpdating($this->target->update($stocks));
            }
        );
    }

    /**
     * Валидация настроек
     *
     * @param array $settings Настройки
     *
     * @return void
     */
    protected function validate(array $settings): void
    {
        v::key(
            'relations', v::anyOf(
                v::each(v::keySet(
                    v::key('source', v::each(
                        v::stringType()->length(36, 36)
                    )),
                    v::key('target', v::intType())
                )),
                v::equals([])
            )
        )->assert($settings);
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
        $this->validate(
            $settings = [
                'relations' => $relations = $settings['relations'] ?? []
            ]
        );

        $this->event(new ShortStocksReceiving);
        $this->eventCatch($promise = $this->getStocks($relations));
        $promise->then(
            function ($stocks) use ($relations) {
                $this->event(new ShortStocksReceived($stocks));
                $this->event(
                    new ReceivingArticlesByIds(
                        $ids = array_unique(
                            array_map(fn ($item) => $item->assortmentId, $stocks)
                        )
                    )
                );

                $this->eventArticles($ids, $promise = $this->getArticlesByIds($ids));

                if ($relations) {
                    if (count($relations) === 1) {
                        if (count($relations[0]['source']) === 1) {
                            $this->syncOneToOne(
                                $promise,
                                $stocks,
                                $relations[0]['target']
                            );
                            return $stocks;
                        }
                    }

                    return $this->syncWithStores($promise, $stocks, $relations);
                }

                $this->syncWithoutStores($promise, $stocks);
            },
            function ($exception) {
                $this->event(new ErrorReceivingShortStocks($exception));
            }
        );

        return true;
    }
}
