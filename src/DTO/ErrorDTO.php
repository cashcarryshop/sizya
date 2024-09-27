<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
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

use CashCarryShop\Sizya\Exceptions\BadResponseException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;

/**
 * DTO Ошибки.
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @const TYPES      Типы ошибок
 * @const VALIDATION Тип ошибки `validation`. Ошибка валидации данных
 * @const UNDEFINED  Тип ошибки `undefined`.  Неизвестная ошибка
 * @const NOT_FOUND  Тип ошибки `not_found`.  Элемент не найден
 * @const INTERNAL   Тип ошибки `internal`.   Ошибка в коде
 * @const HTTP       Тип ошибки `http`.       Ошибка при запросе по протоколу http
 *
 * @property string $type   Тип ошибки (см. выше)
 * @property mixed  $reason Причина ошибки (тип зависит от правил валидации)
 */
class ErrorDTO extends AbstractDTO
{
    public const TYPES = [
        'validation',
        'undefined',
        'not_found',
        'internal',
        'http',
        'api'
    ];

    public const VALIDATION = 'validation';
    public const UNDEFINED  = 'undefined';
    public const NOT_FOUND  = 'not_found';
    public const INTERNAL   = 'internal';
    public const HTTP       = 'http';
    public const API        = 'api';

    /**
     * Создать экземпляр ошибки
     *
     * @param string $type   Тип ошибки (должен быть одним из значений константы TYPES)
     * @param mixed  $reason Причина ошибки (тип зависит от правил валидации)
     */
    public function __construct(
        #[Asert\Type('string')]
        #[Assert\NotBlank]
        #[Assert\Choice(ErrorDTO::TYPES)]
        public $type = null,

        #[Assert\Valid]
        #[Assert\When(
            expression: 'this.type not in ["not_found"]',
            constraints: [new Assert\NotBlank]
        )]
        #[Assert\When(
            expression: 'this.type === "validation"',
            constraints: [
                new Assert\Type(ConstraintViolationListInterface::class),
            ]
        )]
        #[Assert\When(
            expression: 'this.type === "internal"',
            constraints: [new Assert\Type(Throwable::class)]
        )]
        #[Assert\When(
            expression: 'this.type === "http"',
            constraints: [new Assert\Type(BadResponseException::class)]
        )]
        #[Assert\When(
            expression: 'this.type === "api"',
            constraints: [new Assert\Type(ApiErrorsDTO::class)]
        )]
        public $reason = null
    ) {}
}
