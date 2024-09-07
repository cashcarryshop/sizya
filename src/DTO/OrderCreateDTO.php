<?php
declare(strict_types=1);
/**
 * DTO для создания заказов
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
 * DTO для создания заказов
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property ?string               $created        Дата создания заказа
 * @property ?string               $status         Статус заказа
 * @property ?string               $shipmentDate   Планируемаая дата отгрузки
 * @property ?string               $article        Артикул товара
 * @property ?string               $deliveringDate Дата передачи заказа в доставку
 * @property ?string               $description    Описание
 * @property AdditionalCreateDTO[] $additionals    Доп. поля заказа
 * @property PositionCreateDTO[]   $positions      Позиции заказа
 *
 * @see CreateAdditionalDTO
 * @see CreatePositionDTO
 */
class OrderCreateDTO extends AbstractDTO
{

    /**
     * Создать экземпляр заказа
     *
     * Все даты должны быть в формате UTC `Y-m-d\TH:i:s\Z`
     *
     * @param ?string               $created        Дата создания заказа
     * @param ?string               $status         Статус заказа
     * @param ?string               $shipmentDate   Планируемаая дата отгрузки
     * @param ?string               $article        Артикул товара
     * @param ?string               $deliveringDate Дата передачи заказа в доставку
     * @param ?string               $description    Описание
     * @param AdditionalCreateDTO[] $additionals    Доп. поля заказа
     * @param PositionCreateDTO[]   $positions      Позиции заказа
     *
     * @see CreateAdditionalDTO
     * @see CreatePositionDTO
     */
    public function __construct(
        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.article !== null',
            constraints: [new Assert\NotBlank]
        )]
        public readonly mixed $article = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.created !== null',
            constraints: [new Assert\DateTime('Y-m-d\TH:i:s\Z')]
        )]
        public readonly mixed $created = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.status !== null',
            constraints: [new Assert\NotBlank]
        )]
        public readonly mixed $status = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.shipmentDate !== null',
            constraints: [new Assert\DateTime('Y-m-d\TH:i:s\Z')]
        )]
        public readonly mixed $shipmentDate = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.deliveringDate !== null',
            constraints: [new Assert\DateTime('Y-m-d\TH:i:s\Z')]
        )]
        public readonly mixed $deliveringDate = null,

        #[Assert\Type(['string', 'null'])]
        public readonly mixed $description = null,

        #[Assert\Type('array')]
        #[Assert\All(new Assert\Type(AdditionalCreateDTO::class))]
        public readonly mixed $additionals = [],

        #[Assert\Type('array')]
        #[Assert\All(new Assert\Type(PositionCreateDTO::class))]
        public readonly mixed $positions = []
    ) {}
}
