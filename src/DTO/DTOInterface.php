<?php
/**
 * Интерфейс объекта передачи данных
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

use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Интерфейс объекта передачи данных
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface DTOInterface
{
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
     * Конвертировать в json
     *
     * @return string
     */
    public function toJson(): string;
}
