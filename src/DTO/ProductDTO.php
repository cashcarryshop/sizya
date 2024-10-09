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
 * DTO для товаров.
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see DTOInterface
 *
 * @property string     $id       Идентификатор товара
 * @property string     $article  Артикул товара
 * @property string     $created  Дата создания товара
 * @property PriceDTO[] $prices   Цены товара
 * @property mixed      $original Исходные данные
 */
class ProductDTO extends AbstractDTO
{
    /**
     * Создать экземпляр товара.
     *
     * @param string     $id       Идентификатор товара
     * @param string     $article  Артикул товара
     * @param string     $created  Дата создания товара
     * @param PriceDTO[] $prices   Цены товара
     * @param mixed      $original Исходные данные
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
        #[Assert\DateTime(ProductDTO::DATE_FORMAT)]
        public $created = null,

        #[Assert\Type('array')]
        #[Assert\All(new Assert\Type(PriceDTO::class))]
        public $prices = [],

        #[Assert\NotBlank]
        public $original = null
    ) {}
}
