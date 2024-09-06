<?php
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
use CashCarryShop\Sizya\Validator\Constraints\Instance;

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
     * Идентификатор заказа
     *
     * @var string
     */
    #[Assert\NotBlank]
    public readonly string $id;

    /**
     * Артикул
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this.article !== null',
        constraints: [new Assert\NotBlank]
    )]
    public readonly ?string $article;

    /**
     * Дата создания заказа
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this.created !== null',
        constraints: [new Assert\DateTime('Y-m-d\TH:i:s\Z')]
    )]
    public readonly ?string $created;

    /**
     * Статус заказа
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this.status !== null',
        constraints: [new Assert\NotBlank]
    )]
    public readonly ?string $status;

    /**
     * Планованя дата отгрузки
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this.shipmentDate !== null',
        constraints: [new Assert\DateTime('Y-m-d\TH:i:s\Z')]
    )]
    public readonly ?string $shipmentDate;

    /**
     * Дата передачи заказа в доставку
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this.deliveringDate !== null',
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
     * @var AdditionalUpdateDTO[]
     */
    #[Assert\All(new Instance(AdditionalUpdateDTO::class))]
    public readonly array $additionals;

    /**
     * Позиции
     *
     * @var PositionUpdateDTO[]
     */
    #[Assert\All(new Instance(PositionUpdateDTO::class))]
    public readonly array $positions;

    /**
     * Создать экземпляр заказа
     *
     * Все даты должны быть в формате UTC `Y-m-d\TH:i:s\Z`
     *
     * @param string                $id             Идентификатор
     * @param ?string               $created        Дата создания заказа
     * @param ?string               $status         Статус заказа
     * @param ?string               $shipmentDate   Планируемаая дата отгрузки
     * @param ?string               $article        Артикул товара
     * @param ?string               $deliveringDate Дата передачи заказа в доставку
     * @param ?string               $description    Описание
     * @param AdditionalUpdateDTO[] $additionals    Доп. поля заказа
     * @param PositionUpdateDTO[]   $positions      Позиции заказа
     *
     * @see AdditionaUpdatelDTO
     * @see PositionUpdateDTO
     */
    public function __construct(
        string  $id,
        ?string $created        = null,
        ?string $status         = null,
        ?string $shipmentDate   = null,
        ?string $article        = null,
        ?string $deliveringDate = null,
        ?string $description    = null,
        array   $additionals    = [],
        array   $positions      = []
    ) {
        $this->id             = $id;
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
