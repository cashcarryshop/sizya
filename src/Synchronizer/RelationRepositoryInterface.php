<?php
/**
 * Элемент синхронизации, взаимодействующий с протоколом Http
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
 * Элемент синхронизации, взаимодействующий с протоколом Http
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
     * @return bool
     */
    public function create(string $sourceId, string $targetId): bool;

    /**
     * Проверить что отношение существует
     * по идентификатору источника
     *
     * @param string $sourceId Идентификатор источника
     *
     * @return bool
     */
    public function hasTarget(string $sourceId): bool;

    /**
     * Проверить что отношение существует по
     * идентификатору цели
     *
     * @param string $targetId Идентификатор цели
     *
     * @return bool
     */
    public function hasSource(string $targetId): bool;

    /**
     * Получить идентификатор цели по идентификатору источника
     *
     * @param string $sourceId Идентификатор источника
     *
     * @return ?string
     */
    public function getTargetId(string $sourceId): ?string;

    /**
     * Получить идентификатор источника по идентификатору цели
     *
     * @param string $targetId Идентификатор цели
     *
     * @return ?string
     */
    public function getSourceId(string $targetId): ?string;

    /**
     * Добавить (или обновить) данные источника
     *
     * @param string $sourceId Идентификатор источника
     * @param array  $options  Опции
     *
     * @return bool
     */
    public function addSourceOptions(string $sourceId, array $options): bool;

    /**
     * Добавить (или обновить) данные цели
     *
     * @param string $targetId Идентификатор цели
     * @param array  $options  Опции
     *
     * @return bool
     */
    public function addTargetOptions(string $targetId, array $options): bool;

    /**
     * Получить опции источника
     *
     * @param string $sourceId Идентификатор источника
     *
     * @return array
     */
    public function getSourceOptions(string $sourceId): array;

    /**
     * Получить опции цели
     *
     * @param string $targetId Идентификатор цели
     *
     * @return array
     */
    public function getTargetOptions(string $targetId): array;
}
