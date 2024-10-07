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
    public array $relations;

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
        $ids = \array_column($this->relations, 'sourceId');

        $items = [];
        foreach ($sourceIds as $sourceId) {
            $key = \array_search($sourceId, $ids);

            if ($key === false) {
                continue;
            }

            $items[] = $this->relations[$key];
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
        $ids = \array_column($this->relations, 'targetId');

        $items = [];
        foreach ($targetIds as $targetId) {
            $key = \array_search($targetId, $ids);

            if ($key === false) {
                continue;
            }

            $items[] = $this->relations[$key];
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
