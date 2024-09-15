<?php
declare(strict_types=1);
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

use InvalidArgumentException;
use JsonException;

use function json_encode, is_string;

/**
 * Абстрактный класс DTO с реализацией основных методов.
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
     * Создать dto из json
     *
     * @param string $json Данные в json
     *
     * @return static
     * @throws JsonException Если невозможно декодировать json
     */
    public function fromJson(string $json): static
    {
        return static::fromArray(
            json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            )
        );
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
