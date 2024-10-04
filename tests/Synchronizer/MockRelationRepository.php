<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category TestMock
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Tests\Synchronizer;

use CashCarryShop\Sizya\DTO\RelationDTO;
use CashCarryShop\Sizya\Synchronizer\RelationRepositoryInterface;

/**
 * Тестовый класс репозитория отношений.
 *
 * @category TestMock
 * @package  Sizya
 * @author   TheWhatis <anton-gnogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class MockRelationRepository implements RelationRepositoryInterface
{
    /**
     * Отношения элементов.
     *
     * @var array
     */
    protected array $relations;

    /**
     * Создать репозиторий отношений.
     *
     * @param array $relations Отношения
     */
    public function __construct(array $relations)
    {
        $this->relations = $relations;
    }

    /**
     * Создать отношение
     *
     * @param RelationDTO $relation Отношение
     *
     * @see RelationDTO
     *
     * @return bool Создана ли связь
     */
    public function create(RelationDTO $relation): bool
    {
        $this->relations[] = $relation;
        return true;
    }

    /**
     * Удалить связь
     *
     * @param RelationDTO $relation Отношение
     *
     * @see RelationDTO
     *
     * @return bool Удалена ли связь
     */
    public function destroy(RelationDTO $relation): bool
    {
        foreach ($this->relations as $idx => $item) {
            if ($relation->sourceId === $item->sourceId
                && $relation->targetId === $item->targetId
            ) {
                unset($this->relations[$idx]);
                $this->relations = \array_values($this->relations);
                return true;
            }
        }

        return false;
    }

    /**
     * Получить связи по идентификаторам источников
     *
     * @param string[] $sourceIds Идентификаторы источников
     *
     * @see RelationDTO
     *
     * @return RelationDTO[]
     */
    public function getBySourceIds(array $sourceIds): array
    {
        \array_multisort(
            \array_column($this->relations, 'sourceId'),
            SORT_STRING,
            $this->relations
        );

        \asort($targetIds);

        $items = [];

        \reset($this->relations);
        foreach ($sourceIds as $sourceId) {
            $current = \current($this->relations);
            if ($current === null) {
                break;
            }

            if ($sourceId === $current->sourceId) {
                $items[] = $current;
                \next($current);
            }
        }

        return $items;
    }

    /**
     * Получить связи по идентификаторe источника
     *
     * @param string $sourceId Идентификатор источника
     *
     * @see RelationDTO
     *
     * @return ?RelationDTO
     */
    public function getBySourceId(string $sourceId): ?RelationDTO
    {
        return $this->getBySourceIds([$sourceId])[0] ?? null;
    }

    /**
     * Получить связи по идентификаторам целей
     *
     * @param array<string> $targetIds Идентификаторы целей
     *
     * @see RelationDTO
     *
     * @return RelationDTO[]
     */
    public function getByTargetIds(array $targetIds): array
    {
        \array_multisort(
            \array_column($this->relations, 'targetId'),
            SORT_STRING,
            $this->relations
        );

        \asort($targetIds);

        $items = [];

        \reset($this->relations);
        foreach ($targetIds as $targetId) {
            $current = \current($this->relations);
            if ($current === null) {
                break;
            }

            if ($targetId === $current->targetId) {
                $items[] = $current;
                \next($current);
            }
        }

        return $items;
    }

    /**
     * получить связи по идентификатору цели
     *
     * @param string $targetId Идентификаторы целей
     *
     * @see RelationDTO
     *
     * @return ?RelationDTO
     */
    public function getByTargetId(string $targetId): ?RelationDTO
    {
        return $this->getByTargetIds([$targetId])[0];
    }
}
