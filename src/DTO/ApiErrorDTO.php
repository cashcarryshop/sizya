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

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO Ошибки API.
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @param string $message Текст ошибки
 * @param mixed  $original Оригинальное значение
 * @param mixed  $value   Значение с ошибкой
 */
class ApiErrorDTO extends AbstractDTO
{
    /**
     * Создать экземпляр ошибки
     *
     * @param string $message  Текст ошибки
     * @param mixed  $original Оригинальное значение
     * @param mixed  $value    Значение с ошибкой
     */
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $message = null,

        #[Assert\NotBlank]
        public $original = null,
        public $value    = null,
    ) {}
}
