<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Exceptions
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Exceptions;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Exception;
use Throwable;

/**
 * Ошибка валидации.
 *
 * PHP version 8
 *
 * @category Exceptions
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class ValidationException extends Exception
{
    /**
     * Список ошибок валидации
     *
     * @var ConstraintViolationListInterface
     */
    protected ConstraintViolationListInterface $violations;

    /**
     * Создание исключения
     *
     * @param string                           $message    Сообщеине
     * @param int                              $code       Код ошибки
     * @param Throwable                        $previous   Предыдущая ошибка
     * @param ConstraintViolationListInterface $violations Список ошибок валидации
     */
    public function __construct(
        string                           $message    = 'Validated entity have some violations',
        int                              $code       = 0,
        Throwable                        $previous   = null,
        ConstraintViolationListInterface $violations = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->violations = $violations;
    }

    /**
     * Конвертация в строку
     *
     * @return string
     */
    public function __toString(): string
    {
        return parent::__toString() . PHP_EOL
            . PHP_EOL . (string) $this->violations;
    }

    /**
     * Получить ошибки валидации
     *
     * @return ConstraintViolationListInterface
     */
    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}
