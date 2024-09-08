<?php
declare(strict_types=1);
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
     * Создать экземпляр доп. поля
     *
     * @param string  $id       Идентификатор конкретно этого доп. поля
     * @param string  $entityId Идентификатор сущности доп. поля
     * @param ?string $name     Название доп. поля
     * @param mixed   $value    Значение доп поля
     * @param mixed   $original Исходные данные
     */
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $id = null,

        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $entityId = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.id !== null',
            constraints: [new Assert\NotBlank]
        )]
        public $name  = null,
        public $value = null,


        #[Assert\NotBlank]
        public $original = null
    ) {}
}
