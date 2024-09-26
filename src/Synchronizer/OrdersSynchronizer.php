<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya
 *
 * PHP version 8
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Synchronizer;

use CashCarryShop\Synchronizer\SynchronizerSourceInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Sizya\OrdersGetterInterface;
use CashCarryShop\Sizya\OrdersGetterByAdditionalInterface;
use CashCarryShop\Sizya\OrdersCreatorInterface;
use CashCarryShop\Sizya\OrdersUpdaterInterface;
use CashCarryShop\Sizya\Events\Success;
use Respect\Validation\Validator as v;

/**
 * Синхронизатор заказов
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class OrdersSynchronizer extends AbstractSynchronizer
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
        return $source instanceof OrdersGetterInterface;
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
        return $target instanceof OrdersGetterInterface
            && $target instanceof OrdersCreatorInterface
            || $target instanceof OrdersUpdaterInterface;
    }

    /**
     * Сопоставить данные отношений
     *
     * @param array $orders    Заказы
     * @param array $relations Отношения заказов
     *
     * @return void
     */
    private function _compareRelations(array $orders, array &$relations): void
    {
        $sourceIds = array_column($orders, 'id');
        asort($sourceIds, SORT_ASC);

        $ids = array_column($relations, 'source');
        asort($ids, SORT_ASC);

        $diff = array_diff($sourceIds, $ids);

        $result = [];
        foreach (array_keys($sourceIds) as $index) {
            if (isset($diff[$index])) {
                $result[$index] = null;
                continue;
            }

            $result[$index] = $relations[key($ids)];
            next($ids);
        }
        $relations = $result;

        unset($sourceIds);
    }

    /**
     * Дополнить данные с помощью доп. поля
     *
     * @param string $additional Доп. поле
     * @param array  $orders     Заказы
     * @param array  $relations  Ссылка на отношения заказов
     * @param array  $targets    Целевые заказы
     * @param object $repository Репозиторий
     *
     * @return void
     */
    private function _supplimentByAdditional(
        string $additional,
        array $orders,
        array &$relations,
        array &$targets,
        object $repository
    ): void {
        $ids = array_column($orders, 'id');
        $sourceIds = array_column($relations, 'sourceId');

        if ($notFound = array_diff($ids, $sourceIds)) {
            $byAdditional = $this->target->getOrdersByAdditional(
                $additional,
                $notFound
            );

            $values = [];
            foreach (array_column($byAdditional, 'additional') as $addRecords) {
                foreach ($addRecords as $item) {
                    if ($item['entityId'] === $additional) {
                        $values[] = $item['value'];
                        continue 2;
                    }
                }
            }

            asort($notFound, SORT_ASC);
            asort($values, SORT_ASC);

            foreach ($notFound as $index => $sourceId) {
                if (current($values) === $sourceId) {
                    $idx = key($values);
                    $repository->create(
                        $sourceId,
                        $targetId = $byAdditional[$idx]['id']
                    );

                    $relations[$index] = [
                        'sourceId' => $sourceId,
                        'targetId' => $targetId
                    ];

                    $targets[] = $byAdditional[$idx];
                    next($values);
                }
            }
        }
    }

    /**
     * Сопоставить данные
     *
     * @param array $relations Ссылка на отношение заказов и целей
     * @param array $targets   Ссылка на цели
     *
     * @return void
     */
    private function _compareTargets(array $relations, array &$targets): void
    {
        $targetIds = array_column($targets, 'id');
        asort($targetIds, SORT_ASC);

        $ids = array_map(
            static fn ($relation) => $relation['targetId'] ?? null,
            $relations
        );
        asort($ids, SORT_ASC);

        $diff = array_diff($ids, $targetIds);

        $result = [];
        foreach ($ids as $index => $value) {
            if ($value === null || isset($diff[$index])) {
                $result[$index] = null;
                continue;
            }

            $result[$index] = $targets[key($targetIds)];
            next($targetIds);
        }

        $targets = $result;
    }

    /**
     * Получить набор данных для обработки данных
     * для цели (для метода _prepareTargetData)
     *
     * @param array $orders   Заказы
     * @param array $settings Настройки
     *
     * @return array
     */
    private function _bundleForPrepareTargetData(array $orders, array $settings): array
    {
        $cache = $settings['cache'] ?? false;
        $repository = $settings['repository'];

        $relations = $repository->getBySourceIds(array_column($orders, 'id'));
        $targetIds = array_column($relations, 'targetId');

        $targets = [];
        if ($cache) {
            $targets = $cache->getItems($targetIds);
        }

        if ($notFound = array_diff($targetIds, array_column($targets, 'id'))) {
            $targets = array_merge($targets, $this->target->getOrdersByIds($notFound));
        }
        unset($notFound);
        unset($targetIds);

        $this->_compareRelations($orders, $relations);
        if ($settings['additional'] ?? false) {
            $this->_supplimentByAdditional(
                $settings['additional'],
                $orders,
                $relations,
                $targets,
                $repository
            );
        }
        $this->_compareTargets($relations, $targets);

        return [
            $this->target instanceof OrdersCreatorInterface && $settings['doCreate'],
            $this->target instanceof OrdersUpdaterInterface && $settings['doUpdate'],
            [],
            [],
            array_column($settings['status'], 'source'),
            array_column($settings['status'], 'target'),
            static function ($position) {
                unset($position['id']);
                unset($position['original']);
                unset($position['orderId']);

                return $position;
            },
            $cache = $settings['cache'] ?? false,
            $repository,
            count($orders),
            $relations,
            $targets
        ];
    }

    /**
     * Добавить значение по ключу если есть различия
     *
     * @param array  $previous Предыдущее значение
     * @param array  $current  Текущее значение
     * @param array  $item     Ссылка на элемент
     * @param string $key      Ключ
     *
     * @return bool
     */
    private function _setIfDiff(
        array $previous,
        array $current,
        array &$item,
        string $key
    ): bool {
        if (isset($previous[$key], $current[$key])) {
            if ($previous[$key] !== $current[$key]) {
                $item[$key] = $current[$key];
                return true;
            }
        }

        return false;
    }

    /**
     * Установить если значение существует
     *
     * @param array  $data Данные
     * @param array  $item Ссылка на элемент
     * @param string $key  Ключ
     *
     * @return bool
     */
    private function _setIfExists(array $data, array &$item, string $key): bool
    {
        if (isset($data[$key])) {
            $item[$key] = $data[$key];
            return true;
        }

        return false;
    }

    /**
     * Установить статус соответственно переданному
     * их отношению
     *
     * @param array  $item    Ссылка на массив, куда пихать статус
     * @param string $status  Текущий статус
     * @param array  $sStatus Статусы источников
     * @param array  $tStatus Статусы целей
     *
     * @return bool
     */
    private function _findAndAddStatus(
        array &$item,
        string $status,
        array $sStatus,
        array $tStatus
    ): bool {
        $index = array_search($status, $sStatus);
        if ($index !== false) {
            $item['status'] = $tStatus[$index];
            return true;
        }

        return false;
    }

    /**
     * Собрать данные для обновления ориентируясь
     * на кэшериованные данные
     *
     * @param array     $item      Ссылка на массив, куда пихать данные
     * @param ?callable $formatter Форматировщик данных для позиций
     * @param array     $target    Целевой заказ
     * @param array     $current   Текущие данные
     * @param array     $sStatus   Статусы источников (SourceStatuses)
     * @param array     $tStatus   Статусы целей (TargetStatuses)
     *
     * @return bool Было ли добавлено хоть 1 свойство
     */
    private function _addUpdateData(
        array &$item,
        ?callable $formatter,
        array $target,
        array $current,
        array $sStatus,
        array $tStatus
    ): bool {
        $added = false;

        $this->_findAndAddStatus($current, $current['status'], $sStatus, $tStatus)
            && $this->_setIfDiff($target, $current, $item, 'status')
            && $added = true;

        // $formatter
            // && $this->_setIfDiff($target, $current, $target, 'positions')
            // && $item['positions'] = array_map($formatter, $target['positions'])
            // && $added = true;

        $this->_setIfDiff($target, $current, $item, 'article') && $added = true;
        $this->_setIfDiff($target, $current, $item, 'shipment_date') && $added = true;
        $this->_setIfDiff($target, $current, $item, 'delivery_date') && $added = true;

        return $added;
    }

    /**
     * Добавить заказ в массив через Middleware
     *
     * В массиве $additional:
     *
     * - source: (array)  Данные источника
     * - target: (?array) Цель, текущие данные заказа, только при обновлении
     *
     * @param int       $index      Индекс элемента
     * @param array     $data       Данные для обновления. создания
     * @param array     $array      Ссылка на масссив куда добавить заказ
     * @param ?callable $middleware Посредник
     * @param array     $additional Доп. данные
     *
     *
     * @return void
     */
    public function _addThroughMiddleware(
        int $index,
        array $data,
        array &$array,
        ?callable $middleware,
        array $additional
    ): void {
        if ($middleware === null) {
            $array[$index] = $data;
            return;
        }

        call_user_func(
            $middleware,
            $data,
            static function ($data) use ($index, &$array) {
                $array[$index] = $data;
            },
            $additional
        );
    }

    /**
     * Подготовить данные для цели
     *
     * @param array $orders   Заказы источника
     * @param array $settings Настройки
     *
     * @return array Данные для обновления, создание данных
     */
    private function _prepareTargetData(array $orders, array $settings): array
    {
        list(
            $doCreate,
            $doUpdate,
            $create,
            $update,
            $sStatus,
            $tStatus,
            $pFormatter,
            $cache,
            $repository,
            $count,
            $relations,
            $targets,
        ) = $this->_bundleForPrepareTargetData($orders, $settings);

        if (!$doCreate && !$doUpdate) {
            return [$create, $update];
        }

        for ($i = 0; $i < $count; ++$i) {
            $item = [];

            // Если найдена связь между заказами, целевой
            // заказ и имеется возможность его обновить - обновляем.
            // Если связь найдена, но целевого заказа нет,
            // удаляем связь
            if ($relation = $relations[$i]) {
                if ($target = $targets[$i]) {
                    if ($doUpdate) {
                        $item['id'] = $relation['targetId'];
                        $this->_addUpdateData(
                            $item,
                            $pFormatter,
                            $target,
                            $orders[$i],
                            $sStatus,
                            $tStatus
                        ) && $this->_addThroughMiddleware(
                            $i,
                            $item,
                            $update,
                            $settings['middleware'] ?? null,
                            [
                                'index' => $i,
                                'sources' => $orders,
                                'targets' => $targets
                            ]
                        );
                    }

                    continue;
                }

                $repository->destroy(...$relation);
            }

            if ($doCreate) {
                $this->_setIfExists($orders[$i], $item, 'article');
                $this->_setIfExists($orders[$i], $item, 'created');
                $this->_setIfExists($orders[$i], $item, 'positions')
                    && $item['positions'] = array_map(
                        $pFormatter, $item['positions']
                    );

                $this->_findAndAddStatus(
                    $item,
                    $orders[$i]['status'],
                    $sStatus,
                    $tStatus
                );

                $this->_setIfExists($orders[$i], $item, 'shipment_date');
                $this->_setIfExists($orders[$i], $item, 'delivery_date');
                $this->_setIfExists($orders[$i], $item, 'currency');
                $settings['additional'] ?? false
                    && $item['additional'] = [
                        $settings['additional'] => $orders[$i]['id']
                    ];

                if ($item) {
                    $this->_addThroughMiddleware(
                        $i,
                        $item,
                        $create,
                        $settings['middleware'] ?? null,
                        [
                            'index' => $i,
                            'sources' => $orders,
                            'targets' => $targets
                        ]
                    );
                }
            }
        }

        $cache && $cache->putItems($update);

        return [$create, $update, $repository, $cache];
    }

    /**
     * Синхронизировать
     *
     * Массив $settings принимает:
     *
     * - optional(doUpdate):   (bool)     Обновлять ли параметры заказа
     * - optional(doCreate):   (bool)     Создавать ли новые заказы
     * - optional(middleware): (callable) Middleware перед доб-ем данных (см. ниже)
     * - optional(repository): (RelationRepositoryInterface) Репозиторий (см. ниже)
     * - optional(status):     (array)    Статусы источников и целей (см. ниже)
     * - optional(additional): (string)   Идентификатор entityId (см. ниже)
     *
     * О `middleware`:
     *
     * Middleware перед добавлением данных для создания/обновления заказа.
     * Принимает набор данных:
     *
     * - $data:       (array)   Данные для обновления/создания заказа
     * - $next:       (Closure) Замыкание, добавляющее данные для обновления/создания
     * - $additional: (array)   Доп. данные
     *
     * В массиве $additional находиться:
     *
     * При создании, в элемент с ключем `target` передается null.
     *
     * - source: (array)  Данные источника (данные заказа из источника)
     * - target: (?array) Данные цели (текущие данные заказа).
     *
     * О `repository`:
     *
     * Репозиторий связей между заказами.
     * Смотреть `RelationRepositoryInterface`.
     *
     * О `status`:
     *
     * Массив с отношениями статусов заказов из источника и цели:
     *
     * - source: (string) Статус источника заказа
     * - target: (string) Статус цели заказа
     *
     * О `additional`:
     *
     * 1. Идентификатор `entityId` для доп. поля заказов target,
     *    работает только в случае, если target наследуется
     *    от интерфейса `OrdersGetterByAdditionalInterface`.
     *
     * 2. По этому полю производиться поиск отношений созданных
     *    заказов, если они не были найдены в репозитории.
     *    Смотреть `OrdersGetterByAdditionalInterface::getByAdditional`.
     *
     * 3. Если отношения по этому полю найдены, создает их
     *    в репозитории и не создает лишние заказы.
     *
     * @param array $settings Настройки для синхронизации
     *
     * @return bool
     */
    protected function process(array $settings): bool
    {
        v::allOf(
            v::key('doUpdate', v::boolType(), false),
            v::key('doCreate', v::boolType(), false),
            v::key('middleware', v::callableType(), false),
            v::key('repository', v::instance(RelationRepositoryInterface::class)),
            v::key('status', v::each(
                v::allOf(
                    v::key('source', v::stringType()),
                    v::key('target', v::stringType())
                )
            ), false),
            v::key('additional', v::stringType(), false),
            v::when(
                v::key('additional', v::stringType()),
                $this->target instanceof OrdersGetterByAdditionalInterface
                    ? v::alwaysValid()
                    : v::alwaysInvalid(),
                v::alwaysValid()
            )
        )->assert($settings);

        $settings = array_replace(['doUpdate' => true, 'doCreate' => true], $settings);

        list(
            $create,
            $update,
            $repository,
            $cache
        ) = $this->_prepareTargetData($orders = $this->source->getOrders(), $settings);

        $updated = [];
        if ($update) {
            $updated = $this->target->massUpdateOrders($update);
        }

        $created = [];
        if ($create) {
            $created = $this->target->massCreateOrders($create);
        }

        $put = [];
        $sources = array_intersect_key($orders, $create);

        // Создание отношений между заказами.
        // Сохранение созданных заказов в кэш.
        foreach ($created as $item) {
            $source = current($sources);
            next($sources);

            if ($item['error']) {
                continue;
            }

            $repository->create($source['id'], $item['id']);
            unset($item['error']);
            $put[] = $item;
        }
        unset($sources);

        if ($cache) {
            // Откат элементов в кэше, которые не удалось обновить.
            foreach ($updated as $item) {
                if ($item['error']) {
                    $put[] = current($update);
                }

                next($update);
            }

            $put && $cache->putItems($put);
        }
        unset($put);

        $this->event(
            new Success([
                'created' => $created,
                'updated' => $updated
            ])
        );

        return true;
    }
}
