<?php
/**
 * DTO для дополнительных полей
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
 * DTO для дополнительных полей
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property ?string $id       Идентификатор конкретно этого доп. поля
 * @property string  $entityId Идентификатор сущности доп. поля
 * @property ?string $name     Название доп. поля
 * @property mixed   $value    Значение доп поля
 * @property mixed   $original Исходные данные
*/
class AdditionalDTO extends AbstractDTO
{
    /**
     * Идентификатор доп. поля
     *
     * @var ?string
     */
    #[Assert\NotBlank]
    public readonly string $id;

    /**
     * Идентификатор доп. поля (для создания, тип доп. поля)
     *
     * @var string
     */
    #[Assert\NotBlank]
    public readonly string $entityId;

    /**
     * Название доп. поля
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this.id !== null',
        constraints: [new Assert\NotBlank]
    )]
    public readonly ?string $name;

    /**
     * Значение
     *
     * @var mixed
     */
    public readonly mixed $value;

    /**
     * Исходные данные
     *
     * @var mixed
     */
    #[Assert\NotBlank]
    public readonly mixed $original;

    /**
     * Создать экземпляр доп. поля
     *
     * @param string  $id       Идентификатор конкретно этого доп. поля
     * @param string  $entityId Идентификатор сущности доп. поля
     * @param mixed   $value    Значение доп поля
     * @param mixed   $original Исходные данные
     * @param ?string $name     Название доп. поля
     */
    public function __construct(
        string  $id,
        string  $entityId,
        mixed   $value,
        mixed   $original,
        ?string $name = null
    ) {
        $this->id       = $id;
        $this->entityId = $entityId;
        $this->name     = $name;
        $this->value    = $value;
        $this->original = $original;
    }
}
