<?php
declare(strict_types=1);
/**
 * DTO для обновления заказов
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
 * DTO для обновления заказов
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property string                $id             Идентификатор
 * @property ?string               $created        Дата создания заказа
 * @property ?string               $status         Статус заказа
 * @property ?string               $shipmentDate   Планируемаая дата отгрузки
 * @property ?string               $article        Артикул товара
 * @property ?string               $deliveringDate Дата передачи заказа в доставку
 * @property ?string               $description    Описание
 * @property AdditionalUpdateDTO[] $additionals    Доп. поля заказа
 * @property PositionUpdateDTO[]   $positions      Позиции заказа
 *
 * @see AdditionalDTO
 * @see PositionDTO
 */
class OrderUpdateDTO extends AbstractDTO
{
    /**
     * Создать экземпляр заказа
     *
     * Все даты должны быть в формате UTC `Y-m-d\TH:i:s\Z`
     *
     * @param string                $id             Идентификатор
     * @param ?string               $article        Артикул товара
     * @param ?string               $created        Дата создания заказа
     * @param ?string               $status         Статус заказа
     * @param ?string               $shipmentDate   Планируемаая дата отгрузки
     * @param ?string               $deliveringDate Дата передачи заказа в доставку
     * @param ?string               $description    Описание
     * @param AdditionalUpdateDTO[] $additionals    Доп. поля заказа
     * @param PositionUpdateDTO[]   $positions      Позиции заказа
     *
     * @see AdditionaUpdatelDTO
     * @see PositionUpdateDTO
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

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.created !== null',
            constraints: [new Assert\DateTime('Y-m-d\TH:i:s\Z')]
        )]
        public $created = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.status !== null',
            constraints: [new Assert\NotBlank]
        )]
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
        #[Assert\All(new Assert\Type(AdditionalUpdateDTO::class))]
        public $additionals = [],

        #[Assert\Type('array')]
        #[Assert\All(new Assert\Type(PositionUpdateDTO::class))]
        public $positions = []
    ) {}
}
