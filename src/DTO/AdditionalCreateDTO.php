<?php
/**
 * DTO для создания дополнительных полей
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
 * DTO для создания дополнительных полей
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
     * Идентификатор доп. поля (для создания, тип доп. поля)
     *
     * @var string
     */
    #[Assert\NotBlank]
    public readonly string $entityId;

    /**
     * Значение
     *
     * @var mixed
     */
    public readonly mixed $value;

    /**
     * Создать экземпляр доп. поля
     *
     * @param string  $entityId Идентификатор сущности доп. поля
     * @param mixed   $value    Значение доп поля
     */
    public function __construct(string $entityId, mixed $value) {
        $this->entityId = $entityId;
        $this->value    = $value;
    }
}
