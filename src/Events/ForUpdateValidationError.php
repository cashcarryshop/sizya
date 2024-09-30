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
use CashCarryShop\Sizya\DTO\OrderUpdateDTO;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Ошибка валидации при получении заказов для
 * их обновления.
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class ForUpdateValidationError
{
    /**
     * Данные источников.
     *
     * @var OrderDTO[]
     */
    public array $sources;

    /**
     * Данные целей.
     *
     * @var OrderDTO[]
     */
    public array $targets;

    /**
     * Ошибочные данные для обновления.
     *
     * @var OrderUpdateDTO[]
     */
    public array $forUpdate;

    /**
     * Ошибки валидации
     *
     * @var ConstraintViolationListInterface
     */
    public ConstraintViolationInterface $violations;

    /**
     * Создание события
     *
     * @param array                            $sources    Источники
     * @param array                            $targets    Цели
     * @param array                            $forUpdate  Данные обновления
     * @param ConstraintViolationListInterface $violations Ошибки валидации
     */
    public function __construct(
        array                            $sources,
        array                            $targets,
        array                            $forUpdate,
        ConstraintViolationListInterface $violations
    ) {
        $this->sources    = $sources;
        $this->targets    = $targets;
        $this->forUpdate  = $forUpdate;
        $this->violations = $violations;
    }
}
