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

use CashCarryShop\Sizya\DTO\RelationDTO;

/**
 * Интерфейс репозитория отношений между элементами.
 *
 * Позволяет получить, удалить или создать
 * отношения между элементами синхронизации
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface RelationRepositoryInterface
{
    /**
     * Создать отношение
     *
     * @param RelationDTO $relation Отношение
     *
     * @see RelationDTO
     *
     * @return bool Создана ли связь
     */
    public function create(RelationDTO $relation): bool;

    /**
     * Удалить связь
     *
     * @param RelationDTO $relation Отношение
     *
     * @see RelationDTO
     *
     * @return bool Удалена ли связь
     */
    public function destroy(RelationDTO $relation): bool;

    /**
     * Получить связи по идентификаторам источников
     *
     * @param string[] $sourceIds Идентификаторы источников
     *
     * @see RelationDTO
     *
     * @return RelationDTO[]
     */
    public function getBySourceIds(array $sourceIds): array;

    /**
     * Получить связи по идентификаторe источника
     *
     * @param string $sourceId Идентификатор источника
     *
     * @see RelationDTO
     *
     * @return ?RelationDTO
     */
    public function getBySourceId(string $sourceId): ?RelationDTO;

    /**
     * Получить связи по идентификаторам целей
     *
     * @param array<string> $targetIds Идентификаторы целей
     *
     * @see RelationDTO
     *
     * @return RelationDTO[]
     */
    public function getByTargetIds(array $targetIds): array;

    /**
     * Получить связи по идентификатору цели
     *
     * @param string $targetId Идентификаторы целей
     *
     * @see RelationDTO
     *
     * @return ?RelationDTO
     */
    public function getByTargetId(string $targetId): ?RelationDTO;
}
