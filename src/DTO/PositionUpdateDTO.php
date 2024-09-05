<?php
/**
 * DTO для обновления позиций
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
 * DTO для обновления позиций
 *
 * Если передать `orderId` вместе с `article`,
 * `orderId` будет приоритетнее.
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property string  $id       Идентификатор позиции
 * @property ?string $orderId  Идентификатор товара
 * @property ?string $article  Артикул товара
 * @property int     $quantity Количество товаров
 * @property int     $reserve  Количество зарезервированных товаров
 * @property float   $price    Цена товара
 * @property float   $discount Скидка
 * @property ?string $type     Тип товара
 * @property ?string $currency Валюта
 * @property ?bool   $vat      Учитывать ли НДС
 */
class PositionUpdateDTO extends AbstractDTO
{
    /**
     * Идентификатор позиции
     *
     * @var string
     */
    #[Assert\NotBlank]
    public readonly string $id;

    /**
     * Идентификатор товара
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this->orderId !== null',
        constraints: [new Assert\NotBlank]
    )]
    public readonly ?string $orderId;

    /**
     * Артикул товара
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this->article !== null',
        constraints: [new Assert\NotBlank]
    )]
    public readonly ?string $article;

    /**
     * Тип товара
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this->type !== null',
        constraints: [new Assert\NotBlank]
    )]
    public readonly ?string $type;

    /**
     * Количество товара
     *
     * @var ?int
     */
    #[Assert\PositiveOrZero]
    public readonly int $quantity;

    /**
     * Сколько товара зарезервировать
     *
     * @var int
     */
    #[Assert\PositiveOrZero]
    public readonly int $reserve;

    /**
     * Стоимость товара
     *
     * @var float
     */
    #[Assert\PositiveOrZero]
    public readonly float $price;

    /**
     * Скидка
     *
     * @var float
     */
    #[Assert\PositiveOrZero]
    public readonly float $discount;

    /**
     * Валюта
     *
     * @var ?string
     */
    #[Assert\When(
        expression: 'this->type !== null',
        constraints: [new Assert\Currency]
    )]
    public readonly ?string $currency;

    /**
     * Учитывать ли НДС
     *
     * @var bool
     */
    public readonly bool $vat;

    /**
     * Создать экземпляр позиции
     *
     * @param string $id Идентификатор позиции
     * @param ?string $orderId  Идентификатор товара
     * @param ?string $article  Артикул товара
     * @param int     $quantity Количество товаров
     * @param int     $reserve  Количество зарезервированных товаров
     * @param float   $price    Цена товара
     * @param float   $discount Скидка
     * @param ?string $type     Тип товара
     * @param ?string $currency Валюта
     * @param ?bool   $vat      Учитывать ли НДС
     */
    public function __construct(
        ?string $orderId   = null,
        ?string $article   = null,
        int     $quantity  = 0,
        int     $reserve   = 0,
        float   $price     = 0,
        float   $discount  = 0,
        ?string $type     = null,
        ?string $currency = null,
        bool    $vat      = false,
    ) {
        $this->order    = $orderId;
        $this->article  = $article;
        $this->quantity = $quantity;
        $this->reserve  = $reserve;
        $this->price    = $price;
        $this->discount = $discount;
        $this->type     = $type;
        $this->currency = $currency;
        $this->vat      = $vat;
    }
}
