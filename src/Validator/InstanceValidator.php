<?php
/**
 * Реализация ограничения Instance
 *
 * PHP version 8
 *
 * @category Validator
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use function is_object, is_string, is_a;

/**
 * Реализация ограничения Instance
 *
 * @category Validator
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class InstanceValidator extends ConstraintValidator
{
    /**
     * Валидировать
     *
     * @param object|string $objectOrClass Объект или класс
     * @param Constraint    $constraint    Ограничение
     *
     * @return void
     */
    public function validate($objectOrClass, Constraint $constraint): void
    {
        if (!$constraint instanceof Constraints\Instance) {
            throw new UnexpectedTypeException($constraint, Constraints\Instance::class);
        }

        if ($constraint->allowString) {
            if (!is_object($objectOrClass) && !is_string($objectOrClass)) {
                throw new UnexpectedValueException($objectOrClass, 'object|string');
            }
        } else {
            if (!is_object($objectOrClass)) {
                throw new UnexpectedValueException($objectOrClass, 'object');
            }
        }

        if (is_a($objectOrClass, $constraint->class, $constraint->allowString)) {
            return;
        }

        $message = $constraint->allowString
            ? $constraint->messageAllowString
            : $constraint->message;

        $this->context
            ->buildViolation($message)
            ->setParameter(':class', $constraint->class)
            ->addViolation();
    }
}
