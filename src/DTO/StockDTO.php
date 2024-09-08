<?php
declare(strict_types=1);
/**
 * DTO для остатков
 *
 * PHP version 8
 *
 * @category Stocks
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для остатков
 *
 * @category Stocks
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @property string  $id          Идентификатор позиции
 * @property string  $article     Артикул товара
 * @property string  $warehouseId Идентификатор склада
 * @property int     $quantity    Количество товаров
 * @property mixed   $original    Оригинальный ответ
 */
class StockDTO extends AbstractDTO
{
    /**
     * Создать экземпляр позиции
     *
     * @param string  $id          Идентификатор товарв
     * @param string  $article     Артикул товара
     * @param string  $warehouseId Идентификатор склада
     * @param int     $quantity    Количество товаров
     * @param mixed   $original    Исходные данные
     */
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $id = null,

        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $article = null,

        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $warehouseId = null,

        #[Assert\Type('int')]
        #[Assert\PositiveOrZero]
        public readonly int $quantity = 0,

        #[Assert\NotBlank]]
        public $original = null
    ) {}
}
