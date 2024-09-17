<?php
declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
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
 * DTO для обновления остатков.
 *
 * @category Stocks
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * Поля `id` и `article` взаимозаменяемые,
 * но обязательные.
 *
 * @property ?string $id          Идентификатор позиции
 * @property ?string $article     Артикул товара
 * @property string  $warehouseId Идентификатор склада
 * @property int     $quantity    Количество товаров
 */
class StockUpdateDTO extends AbstractDTO
{
    /**
     * Создать экземпляр позиции
     *
     * @param ?string $id          Идентификатор товарв
     * @param ?string $article     Артикул товара
     * @param string  $warehouseId Идентификатор склада
     * @param int     $quantity    Количество товаров
     */
    public function __construct(
        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.article === null',
            constraints: [new Assert\NotBlank]
        )]
        public $id = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'this.id === null',
            constraints: [new Assert\NotBlank]
        )]
        public $article = null,

        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $warehouseId = null,

        #[Assert\Type('int')]
        #[Assert\PositiveOrZero]
        public $quantity = 0
    ) {}
}
