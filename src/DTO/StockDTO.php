<?php
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
     * Идентификатор позиции
     *
     * @var string
     */
    #[Assert\NotBlank]
    public readonly string $id;

    /**
     * Артикул товара
     *
     * @var string
     */
    #[Assert\NotBlank]
    public readonly string $article;

    /**
     * Идентификатор склада
     *
     * @var string
     */
    #[Assert\NotBlank]
    public readonly string $warehouseId;

    /**
     * Количество товара
     *
     * @var int
     */
    #[Assert\PositiveOrZero]
    public readonly int $quantity;

    /**
     * Оригинальный ответ
     *
     * @var mixed
     */
    public readonly mixed $original;

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
        string  $id,
        string  $article,
        string  $warehouseId,
        int     $quantity,
        mixed   $original,
    ) {
        $this->id          = $id;
        $this->article     = $article;
        $this->warehouseId = $warehouseId;
        $this->quantity    = $quantity;
        $this->original    = $original;
    }
}
