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
 * DTO для обновления цен товара.
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see DTOInterface
 *
 * @property string $id       Идентификатор товара
 * @property string $article  Артикул товара
 * @property float  $prices   Цены товара
 */
class ProductPricesUpdateDTO extends AbstractDTO
{
    /**
     * Создать экземпляр класса.
     *
     * @param string $id       Идентификатор товара
     * @param string $article  Артикул товара
     * @param array  $prices   Цены товара
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

        #[Assert\Type('array')]
        #[Assert\All(new Assert\Type(PriceUpdateDTO::class))]
        public $prices = [],
    ) {}
}
