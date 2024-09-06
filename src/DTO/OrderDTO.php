<?php
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
use CashCarryShop\Sizya\Validator\Constraints\Instance;

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
        expression: 'this.article != null',
        constraints: [new Assert\NotBlank]
    )]
    public readonly ?string $article;

    /**
     * Дата создания заказа
     *
     * @var string
     */
    #[Assert\DateTime('Y-m-d\TH:i:s\Z')]
    public readonly string $created;

    /**
     * Статус заказа
     *
     * @var string
     */
    #[Assert\NotBlank]
    public readonly string $status;

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
     * @var AdditionalDTO[]
     */
    #[Assert\All(new Instance(AdditionalDTO::class))]
    public readonly array $additionals;

    /**
     * Позиции
     *
     * @var PositionDTO[]
     */
    #[Assert\All(new Instance(PositionDTO::class))]
    public readonly array $positions;

    /**
     * Исходные данные
     *
     * @var mixed
     */
    public readonly mixed $original;

    /**
     * Создать экземпляр заказа
     *
     * Все даты должны быть в формате UTC `Y-m-d\TH:i:s\Z`
     *
     * @param string          $id             Идентификатор
     * @param string          $created        Дата создания заказа
     * @param string          $status         Статус заказа
     * @param mixed           $original       Исходные данные
     * @param ?string         $shipmentDate   Планируемаая дата отгрузки
     * @param ?string         $article        Артикул товара
     * @param ?string         $deliveringDate Дата передачи заказа в доставку
     * @param ?string         $description    Описание
     * @param AdditionalDTO[] $additionals    Доп. поля заказа
     * @param PositionDTO[]   $positions      Позиции заказа
     *
     * @see AdditionalDTO
     * @see PositionDTO
     */
    public function __construct(
        string  $id,
        string  $created,
        string  $status,
        mixed   $original,
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
        $this->original       = $original;
        $this->shipmentDate   = $shipmentDate;
        $this->article        = $article;
        $this->deliveringDate = $deliveringDate;
        $this->description    = $description;
        $this->additionals    = $additionals;
        $this->positions      = $positions;
    }
}
