<?php
/**
 * Ограничение по instanceof
 *
 * PHP version 8
 *
 * @category Constraints
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use CashCarryShop\Sizya\Validator\InstanceValidator;
use Attribute;

/**
 * Ограничение по instanceof
 *
 * @category Constraints
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[Attribute]
class Instance extends Constraint
{
    /**
     * Сообщение об ошибке
     *
     * @var string
     */
    public string $message = 'The object must be an instance of [:class]';

    /**
     * Сообщение об ошибки включая строку
     *
     * @var string
     */
    public string $messageAllowString = 'The object or string must be an instance of [:class]';

    /**
     * Создать ограничение
     *
     * @param string $class       Класс
     * @param bool   $allowString Включая строку
     * @param array  $groups      Группы
     * @param mixed  $payload     Полезная нагрузка
     */
    #[HasNamedArguments]
    public function __construct(
        public string $class,
        public bool   $allowString = false,
        ?array        $groups      = null,
        mixed         $payload     = null
    ) {
        parent::__construct([], $groups, $payload);
    }

    /**
     * Вернуть класс с помощью которого
     * будет производиться валидация
     *
     * @return string
     */
    public function validatedBy(): string
    {
        return InstanceValidator::class;
    }
}
