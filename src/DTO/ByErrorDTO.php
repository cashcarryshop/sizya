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
 * DTO Ошибки со значением, по которому она произошла.
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property mixed $value По какому значению возникла ошибка
 *
 * @see ErrorDTO
 */
class ByErrorDTO extends ErrorDTO
{
    /**
     * Создать экземпляр ошибки
     *
     * @param mixed $value По какому значению возникла ошибка
     */
    public function __construct(
        #[Assert\NotNull]
        public $value = null,
        $type         = null,
        $reason       = null
    ) {
        parent::__construct($type, $reason);
    }
}
