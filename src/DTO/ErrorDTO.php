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

/**
 * DTO Ошибки
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property mixed      $value  По какому значению возникла ошибка
 * @property ReasonDTO  $reason Причина ошибки
 *
 * @see ReasonDTO
 */
class ErrorDTO extends AbstractDTO
{
    /**
     * Создать экземпляр ошибки
     *
     * @param mixed     $value  По какому значению возникла ошибка
     * @param ReasonDTO $reason Причина ошибка
     */
    public function __construct(
        public $value = null,

        #[Assert\Type(ReasonDTO::class)]
        #[Assert\NotBlank]
        #[Assert\Valid]
        public $reason = null
    ) {}
}
