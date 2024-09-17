<?php
/**
 * Этот файл является частью пакета sizya
 *
 * PHP version 8
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Synchronizer;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Трейт с реализацией для работы с валидатором.
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait InteractsWithValidator
{
    /**
     * Валидатор
     *
     * @var ValidatorInterface
     */
    protected ValidatorInterface $validator;

    /**
     * Получить валидатор
     *
     * @return ValidatorInterface
     */
    public function getValidator(): ValidatorInterface
    {
        return $this->validator ??= Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }
}
