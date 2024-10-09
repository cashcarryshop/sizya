<?php declare(strict_types=1);
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
 * DTO для дополнительных полей.
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property ?string $productId Идентификатор товара
 * @property ?string $article   Артикул товара
 * @property int     $quantity  Количество товаров
 * @property int     $reserve   Количество зарезервированных товаров
 * @property float   $price     Цена товара
 * @property float   $discount  Скидка
 * @property ?string $type      Тип товара
 * @property ?string $currency  Валюта
 * @property ?bool   $vat       Учитывать ли НДС
 */
class PositionCreateDTO extends AbstractDTO
{
    /**
     * Создать экземпляр позиции.
     *
     * Параметр $price отображает цену без скидки ($discount).
     *
     * @param ?string $productId Идентификатор товара
     * @param ?string $article   Артикул товара
     * @param ?string $type      Тип товара
     * @param int     $quantity  Количество товаров
     * @param int     $reserve   Количество зарезервированных товаров
     * @param float   $price     Цена товара
     * @param float   $discount  Скидка
     * @param ?string $currency  Валюта
     * @param ?bool   $vat       Учитывать ли НДС
     */
    public function __construct(
        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.article === null',
            constraints: [new Assert\NotBlank]
        )]
        public $productId = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.productId === null',
            constraints: [new Assert\NotBlank]
        )]
        public $article = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'value !== null',
            constraints: [new Assert\NotBlank]
        )]
        public $type = null,

        #[Assert\Type(['int', 'null'])]
        #[Assert\PositiveOrZero]
        public readonly int $quantity = 0,

        #[Assert\Type('int')]
        #[Assert\PositiveOrZero]
        public readonly int $reserve = 0,


        #[Assert\Type(['float'])]
        #[Assert\PositiveOrZero]
        public $price = 0.0,

        #[Assert\Type(['float'])]
        #[Assert\PositiveOrZero]
        public $discount = 0,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'value !== null',
            constraints: [new Assert\Currency]
        )]
        public $currency = null,

        #[Assert\Type('bool')]
        public $vat = false
    ) {}
}
