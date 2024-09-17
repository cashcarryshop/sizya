<?php
declare(strict_types=1);
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
 * DTO Ошибок API.
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see ApiErrorDTO
 *
 * @property array $errors Ошибки api
 */
class ApiErrorsDTO extends AbstractDTO
{
    /**
     * Создать экземпляр ошибки
     *
     * @param array $errors Ошибки api
     */
    public function __construct(
        #[Assert\Type('array')]
        #[Assert\All(new Assert\Type(ApiErrorDTO::class))]
        #[Assert\Valid]
        public $errors = null
    ) {}
}
