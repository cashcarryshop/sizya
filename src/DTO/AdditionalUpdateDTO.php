<?php
/**
 * DTO для обновления дополнительных полей
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
 * DTO для обновления дополнительных полей
 *
 * @property string $id       Идентификатор доп. поля
 * @property string $entityId Идентификатор сущности доп. поля
 * @property mixed  $value    Значение доп поля
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class AdditionalUpdateDTO extends AbstractDTO
{
    /**
     * Идентификатор доп. поля
     *
     * @var string
     */
    #[Assert\NotBlank]
    public readonly string $id;

    /**
     * Идентификатор доп. поля (для создания, тип доп. поля)
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this->entityId !== null',
        constraints: [new Assert\NotBlank]
    )]
    public readonly ?string $entityId;

    /**
     * Значение
     *
     * @var mixed
     */
    public readonly mixed $value;

    /**
     * Создать экземпляр доп. поля
     *
     * @param string   $id       Идентиифкатор доп. поля
     * @param mixed    $value    Значение доп поля
     * @param ?string  $entityId Идентификатор сущности доп. поля
     */
    public function __construct(
        string  $id,
        mixed   $value,
        ?string $entityId = null,
    ) {
        $this->id       = $id;
        $this->value    = $value;
        $this->entityId = $entityId;
    }
}
