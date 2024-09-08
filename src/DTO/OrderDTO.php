<?php
declare(strict_types=1);
/**
 * DTO для заказов
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
 * DTO для заказов
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property string          $id             Идентификатор
 * @property string          $created        Дата создания заказа
 * @property string          $status         Статус заказа
 * @property mixed           $original       Исходные данные
 * @property ?string         $shipmentDate   Планируемаая дата отгрузки
 * @property ?string         $article        Артикул товара
 * @property ?string         $deliveringDate Дата передачи заказа в доставку
 * @property ?string         $description    Описание
 * @property AdditionalDTO[] $additionals    Доп. поля заказа
 * @property PositionDTO[]   $positions      Позиции заказа
 *
 * @see AdditionalDTO
 * @see PositionDTO
 */
class OrderDTO extends AbstractDTO
{

    /**
     * Создать экземпляр заказа
     *
     * Все даты должны быть в формате UTC `Y-m-d\TH:i:s\Z`
     *
     * @param string          $id             Идентификатор
     * @param ?string         $article        Артикул товара
     * @param string          $created        Дата создания заказа
     * @param string          $status         Статус заказа
     * @param ?string         $shipmentDate   Планируемаая дата отгрузки
     * @param ?string         $deliveringDate Дата передачи заказа в доставку
     * @param ?string         $description    Описание
     * @param AdditionalDTO[] $additionals    Доп. поля заказа
     * @param PositionDTO[]   $positions      Позиции заказа
     * @param mixed           $original       Исходные данные
     *
     * @see AdditionalDTO
     * @see PositionDTO
     */
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $id = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.article !== null',
            constraints: [new Assert\NotBlank]
        )]
        public $article = null,

        #[Assert\NotBlank]
        #[Assert\DateTime('Y-m-d\TH:i:s\Z')]
        public $created = null,

        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $status = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.shipmentDate !== null',
            constraints: [new Assert\DateTime('Y-m-d\TH:i:s\Z')]
        )]
        public $shipmentDate = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.deliveringDate !== null',
            constraints: [new Assert\DateTime('Y-m-d\TH:i:s\Z')]
        )]
        public $deliveringDate = null,

        #[Assert\Type(['string', 'null'])]
        public $description = null,

        #[Assert\Type('array')]
        #[Assert\All(new Assert\Type(AdditionalDTO::class))]
        public $additionals = null,

        #[Assert\Type('array')]
        #[Assert\All(new Assert\Type(PositionDTO::class))]
        public $positions = null,

        #[Assert\NotBlank]
        public $original = null
    ) {}
}
