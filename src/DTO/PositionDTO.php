<?php
declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
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
 * DTO для позиций.
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property string  $id       Идентификатор позиции
 * @property string  $orderId  Идентификатор товара
 * @property string  $article  Артикул товара
 * @property int     $quantity Количество товаров
 * @property int     $reserve  Количество зарезервированных товаров
 * @property float   $price    Цена товара
 * @property float   $discount Скидка
 * @property mixed   $original Оригинальный ответ
 * @property ?string $type     Тип товара
 * @property ?string $currency Валюта
 * @property ?bool   $vat      Учитывать ли НДС
 */
class PositionDTO extends AbstractDTO
{

    /**
     * Создать экземпляр позиции
     *
     * @param string  $id       Идентификатор позиции
     * @param string  $orderId  Идентификатор товара
     * @param string  $article  Артикул товара
     * @param ?string $type     Тип товара
     * @param int     $quantity Количество товаров
     * @param int     $reserve  Количество зарезервированных товаров
     * @param float   $price    Цена товара
     * @param float   $discount Скидка
     * @param ?string $currency Валюта
     * @param bool    $vat      Учитывать ли НДС
     * @param mixed   $original Оригинальный ответ
     */
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $id = null,

        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $orderId = null,

        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $article = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'value !== null',
            constraints: [new Assert\NotBlank]
        )]
        public $type = null,

        #[Assert\Type('int')]
        #[Assert\PositiveOrZero]
        public $quantity = 0,

        #[Assert\Type('int')]
        #[Assert\PositiveOrZero]
        public $reserve = 0,

        #[Assert\Type('float')]
        #[Assert\PositiveOrZero]
        public $price = 0.0,

        #[Assert\Type('float')]
        #[Assert\PositiveOrZero]
        public $discount = 0.0,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.type !== null',
            constraints: [new Assert\Currency]
        )]
        public $currency = null,

        #[Assert\Type('bool')]
        public $vat = false,

        #[Assert\NotBlank]
        public $original = null
    ) {}
}
