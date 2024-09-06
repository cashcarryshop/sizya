<?php
/**
 * Абстрактный класс DTO с реализацией основных методов
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

use InvalidArgumentException;

use function json_encode, is_string;

/**
 * Абстрактный класс DTO с реализацией основных методов
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class AbstractDTO implements DTOInterface
{
    /**
     * Создать DTO
     *
     * @param array $data Данные для создания DTO
     *
     * @return static
     * @throw  InvalidArgumentException
     */
    public static function fromArray(array $data): static
    {
        count($data) === count(
            array_filter(
                array_keys($data),
                'is_string'
            )
        ) || throw new InvalidArgumentException(
            'Invalid [data] array. Expected associative array.'
        );

        return new static(...$data);
    }

    /**
     * Конвертировать в массив
     *
     * @return array
     */
    public function toArray(): array
    {
        return (array) $this;
    }

    /**
     * Конвертировать в json
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
