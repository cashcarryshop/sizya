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

use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Validation;

use function json_encode;

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
     */
    public static function create(array $data): static;
    {
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

    /**
     * Валидировать данные
     *
     * @return void
     * @throw  ValidationFailedException
     */
    public function validate(): void
    {
        if (isset($this->validator) && is_a($this->validator, ValidatorInterface::class)) {
            $validator = $this->validator;
        } else {
            $validator = Validation::createValidator();
        }

        $validator->validate($this);
    }
}
