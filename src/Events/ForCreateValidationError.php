<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Events;

use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\OrderCreateDTO;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Ошибка валидации при получении заказов для
 * их создания.
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class ForCreateValidationError
{
    /**
     * Данные источников.
     *
     * @var OrderDTO[]
     */
    public array $sources;

    /**
     * Ошибочные данные для обновления.
     *
     * @var OrderCreateDTO[]
     */
    public array $forCreate;

    /**
     * Ошибки валидации
     *
     * @var ConstraintViolationListInterface
     */
    public ConstraintViolationListInterface $violations;

    /**
     * Создание события
     *
     * @param array                            $sources    Источники
     * @param array                            $forCreate  Данные обновления
     * @param ConstraintViolationListInterface $violations Ошибки валидации
     */
    public function __construct(
        array                            $sources,
        array                            $forCreate,
        ConstraintViolationListInterface $violations
    ) {
        $this->sources    = $sources;
        $this->forCreate  = $forCreate;
        $this->violations = $violations;
    }
}
