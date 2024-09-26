<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\DTO;

use JsonException;

/**
 * Интерфейс объекта передачи данных.
 *
 * Все даты должны быть в формате, который
 * указан в константе DATE_FORMAT.
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface DTOInterface
{
    public const DATE_FORMAT = 'Y-m-d\TH:i:sp';

    /**
     * Создать DTO из массива
     *
     * @param array $data Данные для создания DTO
     *
     * @return static
     */
    public static function fromArray(array $data): static;

    /**
     * Конвертировать в массив
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Создать dto из json
     *
     * @param string $json Данные в json
     *
     * @return static
     * @throws JsonException Если невозможно декодировать json
     */
    public function fromJson(string $json): static;

    /**
     * Конвертировать в json
     *
     * @return string
     */
    public function toJson(): string;
}
