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
use CashCarryShop\Sizya\OrdersGetterByExternalCodesInterface;
use CashCarryShop\Sizya\OrdersCreatorInterface;
use CashCarryShop\Sizya\OrdersUpdaterInterface;
use CashCarryShop\Sizya\Events\Success;
use CashCarryShop\Sizya\Events\ForCreateValidationError;
use CashCarryShop\Sizya\Events\ForUpdateValidationError;
use CashCarryShop\Sizya\Events\OrdersCreated;
use CashCarryShop\Sizya\Events\OrdersUpdated;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\OrderUpdateDTO;
use CashCarryShop\Sizya\DTO\OrderCreateDTO;
use CashCarryShop\Sizya\DTO\PositionDTO;
use CashCarryShop\Sizya\DTO\PositionCreateDTO;
use CashCarryShop\Sizya\DTO\AdditionalCreateDTO;
use CashCarryShop\Sizya\DTO\RelationDTO;
use Symfony\Component\Validator\Constraints as Assert;

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
    use InteractsWithValidator;

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
     * Значение по умолчанию для настроек.
     *
     * @return array
     */
    protected function defaults(): array
    {
        return [
            'doUpdate'   => true,
            'doCreate'   => true,
            'middleware' => null,
            'status'     => [],
            'additional' => null
        ];
    }

    /**
     * Получить правила валидации настроек
     * для метода synchronize.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'doUpdate'   => [new Assert\Type('bool')],
            'doCreate'   => [new Assert\Type('bool')],
            'middleware' => [new Assert\Type(['callable', 'null'])],
            'repository' => [new Assert\Type(RelationRepositoryInterface::class)],
            'status'     => [
                new Assert\NotBlank,
                new Assert\All(
                    new Assert\Collection([
                        'source' => [new Assert\Type('string')],
                        'target' => [new Assert\Type('string')]
                    ])
                )
            ],
            'additional' => [new Assert\Type(['string', 'bool'])],
        ];
    }

    /**
     * Синхронизировать
     *
     * Массив $settings принимает:
     *
     * Смотреть правила валидации выше, метод rules.
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
        $this->_run($settings);

        return true;
    }

    /**
     * Запуск.
     *
     * @param array $settings Настройки
     *
     * @return void
     */
    private function _run(array $settings): void
    {
        $sources    = $this->source->getOrders();
        $sourcesIds = \array_column($sources, 'id');

        $relations          = $settings['repository']->getBySourceIds($sourcesIds);
        $relationsSourceIds = \array_column($relations, 'sourceId');

        \asort($sourcesIds,         SORT_STRING);
        \asort($relationsSourceIds, SORT_STRING);

        $targetsIds  = [];
        $notFound    = [];
        $notFoundIds = [];

        \reset($relationsSourceIds);
        foreach ($sourcesIds as $idx => $sourceId) {
            if (\current($relationsSourceIds) === $sourceId) {
                $targetsIds[$idx] = $relations[\key($relationsSourceIds)]->targetId;
                \next($relationsSourceIds);
                continue;
            }

            $notFound[$idx]    = $sources[$idx];
            $notFoundIds[$idx] = $sourceId;
        }

        $targets = \array_merge(
            $this->target->getOrdersByIds($targetsIds),
            $this->_prepareForExternalCodes([
                'settings'    => $settings,
                'notFound'    => &$notFound,
                'relations'   => &$relations,
                'targetsIds'  => &$targetsIds,
            ]),
            $this->_prepareForAdditionals([
                'settings'    => $settings,
                'notFound'    => &$notFound,
                'notFoundIds' => $notFoundIds,
                'relations'   => &$relations,
                'targetsIds'  => &$targetsIds,
            ])
        );
        unset($notFoundIds);

        $forCreate = [];
        $forUpdate = [];

        $sourceStatuses = \array_column($settings['status'], 'source');
        $targetStatuses = \array_column($settings['status'], 'target');

        $statusRelations = \array_combine(
            $sourceStatuses,
            $targetStatuses
        );

        $forCreate = $this->_makeForCreate([
            'settings'        => $settings,
            'sources'         => $notFound,
            'statusRelations' => $statusRelations
        ]);

        \asort($targetsIds, SORT_STRING);
        \array_multisort(\array_column($targets, 'id'), SORT_STRING, $targets);

        $forUpdate = $this->_makeForUpdate([
            'settings'             => $settings,
            'sources'              => $sources,
            'targets'              => &$targets,
            'associatedTargetsIds' => $targetsIds,
            'statusRelations'      => $statusRelations
        ]);

        $associatedTargets = [];

        \reset($targets);
        foreach ($targetsIds as $idx => $targetId) {
            $associatedTargets[$idx] = \current($targets);
            \next($targets);
        }
        $targets = $associatedTargets;

        unset($associatedTargets);
        unset($targetsIds);
        unset($statusRelations);
        unset($sourceStatuses);
        unset($targetStatuses);

        // Данные для обновления и создания
        // по индексам соответствуют массиву
        // с источниками.
        \ksort($targets);
        \ksort($forUpdate);
        \ksort($forCreate);

        [$forCreate, $forUpdate] = $this->_runDataThroughMiddleware([
            'settings'  => $settings,
            'forCreate' => $forCreate,
            'forUpdate' => $forUpdate,
            'sources'   => $sources,
            'targets'   => $targets
        ]);

        unset($sources);
        unset($targets);

        if ($this->target instanceof OrdersCreatorInterface) {
            // todo: Сделать добавление данных в репозиторий
            // отношений идентификаторов источников и целей.
            $updated = $this->target->massCreateOrders($forCreate);
            $this->event(new OrdersCreated($updated));
        }

        if ($this->target instanceof OrdersUpdaterInterface) {
            $this->event(
                new OrdersUpdated(
                    $this->target->massUpdateOrders($forUpdate)
                )
            );
        }
    }

    /**
     * Получить и обработать данные при получении заказов
     * оп внешнему коду.
     *
     * Массив $data принимает:
     *
     * - settings:  (array)  Настройки
     * - notFound:  (&array) Ссылка на массив с не найденными заказами
     * - relations: (array)  Отношения источников и целей
     *
     * @param array $data Данные
     *
     * @return array
     */
    private function _prepareForExternalCodes(array $data): array
    {
        if (!$this->target instanceof OrdersGetterByExternalCodesInterface) {
            return [];
        }

        [
            'settings'    => $settings,
            'notFound'    => &$notFound,
            'relations'   => &$relations,
            'targetsIds'  => &$targetsIds
        ] = $data;

        $externalCodes = \array_combine(
            \array_keys($notFound),
            \array_column($notFound, 'externalCode')
        );

        $targets = \array_filter(
            $this->target->getOrdersByExternalCodes($externalCodes),
            static fn ($item) => $item instanceof OrderDTO
        );

        \asort($externalCodes, SORT_STRING);
        \array_multisort(
            \array_column($targets, 'externalCode'),
            SORT_STRING,
            $targets
        );

        \reset($targets);
        foreach ($externalCodes as $idx => $externalCode) {
            $current = \current($targets);

            if ($current === false) {
                break;
            }

            if ($current->externalCode === $externalCode) {
                $settings['repository']->create(
                    $relation = RelationDTO::fromArray([
                        'sourceId' => $notFound[$idx]->id,
                        'targetId' => $current->id
                    ])
                );

                $relations[]      = $relation;
                $targetsIds[$idx] = $relation->targetId;

                unset($notFound[$idx]);
                \next($targets);
            }
        }

        return $targets;
    }

    /**
     * Получить и обработать данные при получении заказов
     * оп дополнительному полю.
     *
     * Массив $data принимает:
     *
     * - settings:    (array)  Настройки
     * - notFound:    (&array) Ссылка на массив с не найденными заказами
     * - notFoundIds: (array)  Массив с не найденными идентификаторами заказов
     * - relations:   (array)  Отношения источников и целей
     *
     * @param array $data Данные
     *
     * @return array
     */
    private function _prepareForAdditionals(array $data): array
    {
        if (!$this->target instanceof OrdersGetterByAdditionalInterface) {
            return [];
        }

        [
            'settings'    => $settings,
            'notFound'    => &$notFound,
            'notFoundIds' => $notFoundIds,
            'relations'   => &$relations,
            'targetsIds'  => &$targetsIds
        ] = $data;

        if ($settings['additional']) {
            $targets = \array_filter(
                $this->target->getOrdersByAdditional(
                    $settings['additional'], $notFoundIds
                ),
                static fn ($item) => $item instanceof OrderDTO
            );

            $targetsSourcesIds = \array_map(
                static function ($target) use ($settings) {
                    foreach ($target->additionals as $idx => $additional) {
                        if ($additional->id === $settings['additional']) {
                            return $target->additionals[$idx]->value;
                        }
                    }

                    throw new \RuntimeException(sprintf(
                        'Additional with [%s] id not found',
                        $settings['additional']
                    ));
                },
                $targets
            );

            \asort($targetsSourcesIds, SORT_STRING);

            \reset($targetsSourcesIds);
            foreach ($notFoundIds as $idx => $sourceId) {
                if (\current($targetsSourcesIds) === $sourceId) {
                    $settings['repository']->create(
                        $relation = RelationDTO::fromArray([
                            'sourceId' => $sourceId,
                            'targetId' => $targets[\key($targetsSourcesIds)]->id
                        ])
                    );

                    $relations[]  = $relation;
                    $targetsIds[$idx] = $relation->targetId;

                    unset($notFound[$idx]);
                    \next($targetsSourcesIds);
                }
            }

            return $targets;
        }

        return [];
    }

    /**
     * Собрать данные для создания.
     *
     * Массив $data принимает:
     *
     * - settings:        (array) Настройки
     * - sources:         (array) Источники
     * - statusRelationns (array) Отношения статусов
     *
     * @param array $data Данные
     *
     * @return array
     */
    private function _makeForCreate(array $data): array
    {
        [
            'settings'        => $settings,
            'sources'         => $sources,
            'statusRelations' => $statusRelations
        ] = $data;

        if (!$settings['doCreate']
            || !$this->target instanceof OrdersCreatorInterface
        ) {
            return [];
        }

        $makeCreatePosition = static fn ($position) =>
            PositionCreateDTO::fromArray([
                'article'  => $position->article,
                'quantity' => $position->quantity,
                'reserve'  => $position->reserve,
                'price'    => $position->price,
                'discount' => $position->discount,
                'currency' => $position->currency,
                'vat'      => $position->vat
            ]);

        $forCreate = [];
        foreach ($sources as $idx => $source) {
            $data = [
                'created'        => $source->created,
                'externalCode'   => $source->externalCode,
                'shipmentDate'   => $source->shipmentDate,
                'deliveringDate' => $source->deliveringDate,
                'testKey'        => $source->testKey
            ];

            if (isset($statusRelations[$source->status])) {
                $data['status'] = $statusRelations[$source->status];
            }

            if ($settings['additional']) {
                $data['additionals'] = [
                    AdditionalCreateDTO::fromArray([
                        'entityId' => $settings['additional'],
                        'value'    => $source->id
                    ])
                ];
            }

            $data['positions'] = \array_map($makeCreatePosition, $source->positions);

            $forCreate[$idx] = OrderCreateDTO::fromArray($data);
        }

        return $forCreate;
    }

    /**
     * Создать данные для обновления заказов.
     *
     * Массив $data принимает:
     *
     * - settings:            (array) Настройки
     * - sources:             (array) Источники
     * - targets:             (array) Цели
     * - associatedTargetsIds (array) Идентификаторы найденных целей.
     *                                Индексы у элементов соотносятся с $sources
     *
     * @param array $data Данные
     *
     * @return array
     */
    private function _makeForUpdate(array $data): array
    {
        [
            'settings'             => $settings,
            'sources'              => $sources,
            'targets'              => &$targets,
            'associatedTargetsIds' => $associatedTargetsIds,
            'statusRelations'      => $statusRelations
        ] = $data;

        if (!$settings['doUpdate']
            || !$this->target instanceof OrdersUpdaterInterface
        ) {
            return [];
        }

        $forUpdate = [];
        \reset($targets);
        foreach ($associatedTargetsIds as $associatedIdx => $targetId) {
            $source = $sources[$associatedIdx];
            $target = \current($targets);

            $data = [
                'id' => $target->id,
            ];

            if (isset($statusRelations[$source->status])) {
                if ($statusRelations[$source->status] !== $target->status) {
                    $data['status'] = $statusRelations[$source->status];
                }
            }

            if ($source->shipmentDate !== $target->shipmentDate) {
                $data['shipmentDate'] = $source->shipmentDate;
            }

            if ($source->deliveringDate !== $target->deliveringDate) {
                $data['deliveringDate'] = $source->deliveringDate;
            }

            if ($source->created !== $target->created) {
                $data['created'] = $source->created;
            }

            $forUpdate[$associatedIdx] = OrderUpdateDTO::fromArray($data);

            \next($targets);
        }

        return $forUpdate;
    }

    /**
     * Прогнать данные через middleware.
     *
     * Массив $data принимает:
     *
     * - settings:  (array) Настройки
     * - forCreate: (array) Данные для создания
     * - forUpdate: (array) Данные для обновления
     * - sources:   (array) Источники
     * - targets:   (array) Цели
     *
     * @param array $data Данные
     *
     * @return array<OrderCreateDTO[], OrderUpdateDTO[]>
     */
    private function _runDataThroughMiddleware(array $data): array
    {
        [
            'settings'  => $settings,
            'forCreate' => $forCreate,
            'forUpdate' => $forUpdate,
            'sources'   => $sources,
            'targets'   => $targets
        ] = $data;

        if (!$settings['middleware']) {
            return [$forCreate, $forUpdate];
        }

        $cloneForUpdate = \array_map(
            static fn ($item) => clone $item,
            $forUpdate
        );
        $cloneForCreate = \array_map(
            static fn ($item) => clone $item,
            $forCreate
        );

        $settings['middleware']($cloneForCreate, $cloneForUpdate);

        $violations = $this->getValidator()
            ->validate($cloneForCreate, [
                new Assert\All(new Assert\Type(OrderCreateDTO::class)),
                new Assert\Valid
            ]);

        if ($violations->count()) {
            $this->event(
                new ForCreateValidationError(
                    $sources,
                    $cloneForCreate,
                    $violations
                )
            );
        } else {
            $forCreate = $cloneforCreate;
        }
        unset($cloneForCreate);

        $violations = $this->getValidator()
            ->validate($cloneForUpdate, [
                new Assert\All(new Assert\Type(OrderUpdateDTO::class)),
                new Assert\Valid
            ]);

        if ($violations->count()) {
            $this->event(
                new ForUpdateValidationError(
                    $sources,
                    $targets,
                    $cloneForUpdate,
                    $violations
                )
            );
        } else {
            $forUpdate = $cloneForUpdate;
        }
        unset($cloneForUpdate);

        return [$forCreate, $forUpdate];
    }
}
