<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * Позволяет получать, изменять, создавать
 * кэшированные данные элементов синхронизации.
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
 * Интерфейс для работы с кэшированными
 * данными элемнтов синхронизации.
 *
 * Позволяет получать, изменять, создавать
 * кэшированные данные элементов синхронизации.
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface ItemsCache
{
    /**
     * Получить данные элементов
     *
     * @param array<string> $itemIds Идентификаторы элементов
     *
     * @return array<array>
     */
    public function getItems(array $itemIds): array;

    /**
     * Получить данные элемента
     *
     * @param string $itemId Идентификатор элемента
     *
     * @return ?array
     */
    public function getItem(string $itemId): ?array;

    /**
     * Создать или обновить элементы
     *
     * У элементов обязательно должен быть
     * идентификатор `id`
     *
     * @param array $items Элементы
     *
     * @return bool Были ли созданы элементы
     */
    public function putItems(array $items): bool;

    /**
     * Создать или обновить элемент
     *
     * У массива $data обязательно должно
     * быть поле `id`
     *
     * @param array $data Данные элемента
     *
     * @return bool Был ли создан элемент
     */
    public function putItem(array $data): bool;
}
