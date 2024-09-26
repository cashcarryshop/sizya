<?php declare(strict_types=1);
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
 * DTO для создания заказов.
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
            expression: 'value !== null',
            constraints: [new Assert\NotBlank]
        )]
        public $article = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'value !== null',
            constraints: [new Assert\DateTime(OrderCreateDTO::DATE_FORMAT)]
        )]
        public $created = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'value !== null',
            constraints: [new Assert\NotBlank]
        )]
        public $status = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'value !== null',
            constraints: [new Assert\DateTime(OrderCreateDTO::DATE_FORMAT)]
        )]
        public $shipmentDate = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'value !== null',
            constraints: [new Assert\DateTime(OrderCreateDTO::DATE_FORMAT)]
        )]
        public $deliveringDate = null,

        #[Assert\Type(['string', 'null'])]
        public $description = null,

        #[Assert\Type('array')]
        #[Assert\All(new Assert\Type(AdditionalCreateDTO::class))]
        public $additionals = [],

        #[Assert\Type('array')]
        #[Assert\All(new Assert\Type(PositionCreateDTO::class))]
        public $positions = []
    ) {}
}
