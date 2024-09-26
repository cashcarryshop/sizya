<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для создания дополнительных полей.
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property string $entityId Идентификатор сущности доп. поля
 * @property mixed  $value    Значение доп поля
 */
class AdditionalCreateDTO extends AbstractDTO
{
    /**
     * Создать экземпляр доп. поля
     *
     * @param string  $entityId Идентификатор сущности доп. поля
     * @param mixed   $value    Значение доп поля
     */
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $entityId = null,
        public $value    = null,
    ) {}
}
