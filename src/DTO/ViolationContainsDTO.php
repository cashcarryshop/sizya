<?php
declare(strict_types=1);
/**
 * DTO с индексами нарушений у списка нарушений для значения
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

/**
 * DTO с индексами нарушений у списка нарушений для значения
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property mixed      $value      Значение которое не прошло валидацию
 * @property array<int> $offsets    Индексы под которыми находятся нарушение правил валидации
 * @property mixed      $violations Нарушения правил валидации
 */
class ViolationsContainsDTO extends AbstractDTO
{
    /**
     * Создать экземпляр ошибки
     *
     * @param mixed      $value      Значение которое не прошло валидацию
     * @param array<int> $offsets    Индексы под которыми находятся нарушение правил валидации
     * @param mixed      $violations Нарушения правил валидации
     */
    public function __construct(
        public $value = null,

        #[Assert\NotBlank]
        #[Assert\Type('array')]
        #[Assert\All(new Assert\Type('int'))]
        public $offsets = null,

        #[Assert\NotBlank]
        #[Assert\Type(ConstraintViolationListInterface::class)]
        public $violations = null,
    ) {}
}
