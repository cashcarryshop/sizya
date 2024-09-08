<?php
declare(strict_types=1);
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
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property string $id       Идентификатор доп. поля
 * @property string $entityId Идентификатор сущности доп. поля
 * @property mixed  $value    Значение доп поля
 */
class AdditionalUpdateDTO extends AbstractDTO
{

    /**
     * Создать экземпляр доп. поля
     *
     * @param string   $id       Идентиифкатор доп. поля
     * @param mixed    $value    Значение доп поля
     * @param ?string  $entityId Идентификатор сущности доп. поля
     */
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $id = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.entityId !== null',
            constraints: [new Assert\NotBlank]
        )]
        public $entityId = null,
        public $value    = null
    ) {}
}
