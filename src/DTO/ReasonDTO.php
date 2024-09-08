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
 * DTO Ошибки
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property DTOInterface $source По какому dto произошла ошибка (обновление/создание сущности)
 * @property ReasonDTO    $reason Причина ошибки
 *
 * @see ReasonDTO
 */
class ReasonDTO extends AbstractDTO
{
    /**
     * Типы причин ошибок
     *
     * @const array<int, string>
     */
    public const TYPES = [
        'validation', // Ошибка валидации
        'internal',   // Внутренняя ошибка (ошибки в коде, TypeError и т.д.),
        'http',       // Ошибки при выполнении запросов
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
     * @param string $type Тип причины
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

        #[Assert\Type(['null', ResponseInterface::class])]
        #[Assert\When(
            expression: '$this->type === "http"',
            constraints: [new Assert\NotBlank]
        )]
        public $response = null,

        #[Assert\Type(['null', Throwable::class])]
        #[Assert\When(
            expression: '$this->type === "internal"',
            constraints: [new Assert\NotBlank]
        )]
        public $throwable = null
    ) {
        if (is_int($this->type) && isset($type = static::TYPES[this->type])) {
            $this->type = $type;
        }
    }
}
