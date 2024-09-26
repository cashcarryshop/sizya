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
     * @param string $sourceId Идентификатор источника
     * @param string $targetId Идентификатор цели
     *
     * @return bool Создана ли связь
     */
    public function create(string $sourceId, string $targetId): bool;

    /**
     * Удалить связь
     *
     * @param string $sourceId Идентификатор источника
     * @param string $targetId Идентификатор цели
     *
     * @return bool Удалена ли связь
     */
    public function destroy(string $sourceId, string $targetId): bool;

    /**
     * Получить связи по идентификаторам источников
     *
     * Должен возвращать массив:
     *
     * - sourceId: (string) Идентификатор источника
     * - targetId: (string) Идентификатор цели
     *
     * @param array<string> $sourceIds Идентификаторы источников
     *
     * @return array<array>
     */
    public function getBySourceIds(array $sourceIds): array;

    /**
     * Получить связи по идентификаторe источника
     *
     * Смотреть `RelationRepsotiryInterface::getBySourceIds`
     *
     * @param string $sourceId Идентификатор источника
     *
     * @return array
     */
    public function getBySourceId(string $sourceId): array;

    /**
     * Получить связи по идентификаторам целей
     *
     * Смотреть `RelationRepsotiryInterface::getBySourceIds`
     *
     * @param array<string> $targetIds Идентификаторы целей
     *
     * @return array<array>
     */
    public function getByTargetIds(array $targetIds): array;

    /**
     * Получить связи по идентификатору цели
     *
     * Смотреть `RelationRepsotiryInterface::getByTargetIds`
     *
     * @param string $targetId Идентификаторы целей
     *
     * @return array
     */
    public function getByTargetId(string $targetId): array;
}
