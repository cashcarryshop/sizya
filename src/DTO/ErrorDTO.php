<?php
declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya
 *
 * PHP version 8
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * DTO Ошибки
 *
 * При использовании этого DTO нужно учитывать, что
 * $reason (причина ошибки) должна быть соответствнно
 * типу ошибки:
 *
 * - Если ошибка возникла в ходе валидации полученных данных,
 *   то $reason должен быть `ConstraintViolationListInterface`.
 * - Если ошибка возникла в ходе обращения к API,
 *   то $reason должен быть `ResponseInterface`.
 * - Если возникла непредвиденная ошибка, приводящая
 *   к выводу исключения, то $reason должен быть `Throwable`.
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property mixed $value  По какому значению возникла ошибка
 * @property mixed $reason Причина ошибки (тип зависит от правил валидации в DTO)
 *
 * @see ReasonDTO
 */
class ErrorDTO extends AbstractDTO
{
    /**
     * Создать экземпляр ошибки
     *
     * @param mixed $value  По какому значению возникла ошибка
     * @param mixed $reason Причина ошибка (тип зависит от правил валидации в DTO)
     */
    public function __construct(
        public $value = null,

        #[Assert\NotBlank]
        #[Assert\Type([
            ConstraintViolationListInterface::class,
            ResponseInterface::class,
            Throwable::class
        ])]
        public $reason = null
    ) {}
}
