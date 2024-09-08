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
use Symfony\Component\Validator\ConstraintViolationList;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use function is_int;

/**
 * DTO причины ошибки
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @const TYPES      Типы причин
 * @const VALIDATION Если данные не прошли валидацию
 * @const INTERNAL   Если произошла внутренная ошибка
 * @const HTTP       Если ошибка возникла в ходе http запроса
 *
 * @property string                   $type       Тип ошибки       (см. выше)
 * @property ?ConstraintViolationList $violations Ошибки валидации (при ReasonDTO::VALIDATION)
 * @property ?Throwable               $throwable  Исключение       (при ReasonDTO::INTERNAL)
 * @property ?ResponseInterface       $response   Ответ http       (при ReasonDTO::HTTP)
 */
class ReasonDTO extends AbstractDTO
{
    /**
     * Типы причин ошибок
     *
     * @const array<int, string>
     */
    public const TYPES = [
        'validation',
        'internal',
        'http',
    ];

    /**
     * Тип ошибки validation
     *
     * @const int
     */
    public const VALIDATION = 0;

    /**
     * Тип ошибки internal
     *
     * @const int
     */
    public const INTERNAL = 1;

    /**
     * Тип ошибки http
     *
     * @const int
     */
    public const HTTP = 2;

    /**
     * Создать экземпляр ошибки
     *
     * @param string                   $type       Тип ошибки       (см. ReasonDTO::TYPES)
     * @param ?ConstraintViolationList $violations Ошибки валидации (при ReasonDTO::VALIDATION)
     * @param ?Throwable               $throwable  Исключение       (при ReasonDTO::INTERNAL)
     * @param ?ResponseInterface       $response   Ответ http       (при ReasonDTO::HTTP)
     *
     */
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\Choice(static::TYPES)]
        #[Assert\NotBlank]
        public $type = null,

        #[Assert\Type(['null', ConstraintViolationList::class])]
        #[Assert\When(
            expression: '$this->type === "validation"',
            constraints: [new Assert\NotBlank]
        )]
        public $violations = null,

        #[Assert\Type(['null', Throwable::class])]
        #[Assert\When(
            expression: '$this->type === "internal"',
            constraints: [new Assert\NotBlank]
        )]
        public $throwable = null,

        #[Assert\Type(['null', ResponseInterface::class])]
        #[Assert\When(
            expression: '$this->type === "http"',
            constraints: [new Assert\NotBlank]
        )]
        public $response = null
    ) {
        if (is_int($this->type) && isset($type = static::TYPES[this->type])) {
            $this->type = $type;
        }
    }
}
