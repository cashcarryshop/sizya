<?php
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
use CashCarryShop\Sizya\Validator\Constraints\Instance;

/**
 * DTO для создания заказов
 *
 * @property ?string         $created        Дата создания заказа
 * @property ?string         $status         Статус заказа
 * @property ?string         $shipmentDate   Планируемаая дата отгрузки
 * @property ?string         $article        Артикул товара
 * @property ?string         $deliveringDate Дата передачи заказа в доставку
 * @property ?string         $description    Описание
 * @property AdditionalDTO[] $additionals    Доп. поля заказа
 * @property PositionDTO[]   $positions      Позиции заказа
 *
 * @see CreateAdditionalDTO
 * @see CreatePositionDTO
 *
 * @category Orders
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class CreateOrderDTO extends AbstractDTO
{
    /**
     * Артикул
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this->article !== null',
        constraints: [new Assert\NotBlank]
    )]
    public readonly ?string $article;

    /**
     * Дата создания заказа
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this->created !== null',
        constraints: [new Assert\DateTime('Y-m-d\TH:i:s\Z')]
    )]
    public readonly ?string $created;

    /**
     * Статус заказа
     *
     * @var string
     */
    #[Assert\When(
        expression: 'this->created !== null',
        constraints: [new Assert\NotBlank]
    )]
    public readonly ?string $status;

    /**
     * Планованя дата отгрузки
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this->shipmentDate !== null',
        constraints: [new Assert\DateTime('Y-m-d\TH:i:s\Z')]
    )]
    public readonly ?string $shipmentDate;

    /**
     * Дата передачи заказа в доставку
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this->deliveringDate !== null',
        constraints: [new Assert\DateTime('Y-m-d\TH:i:s\Z')]
    )]
    public readonly ?string $deliveringDate;

    /**
     * Описание
     *
     * @var ?string
     */
    public readonly ?string $description;

    /**
     * Дополнительные поля
     *
     * @var AdditionalDTO[]
     */
    #[Assert\All(new Instance(CreateAdditionalDTO::class))]
    public readonly array $additionals;

    /**
     * Позиции
     *
     * @var PositionDTO[]
     */
    #[Assert\All(new Instance(CreatePositionDTO::class))]
    public readonly array $positions;

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
     * @param CreateAdditionalDTO[] $additionals    Доп. поля заказа
     * @param CreatePositionDTO[]   $positions      Позиции заказа
     *
     * @see CreateAdditionalDTO
     * @see CreatePositionDTO
     */
    public function __construct(
        ?string $created        = null,
        ?string $status         = null,
        ?string $shipmentDate   = null,
        ?string $article        = null,
        ?string $deliveringDate = null,
        ?string $description    = null,
        array   $additionals    = [],
        array   $positions      = []
    ) {
        $this->created        = $created;
        $this->status         = $status;
        $this->shipmentDate   = $shipmentDate;
        $this->article        = $article;
        $this->deliveringDate = $deliveringDate;
        $this->description    = $description;
        $this->additionals    = $additionals;
        $this->positions      = $positions;
    }
}
