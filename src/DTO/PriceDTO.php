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
 * DTO для цен.
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see DTOInterface
 *
 * @property string $id        Идентификатор цены
 * @property float  $value     Значение цены
 * @property mixed  $original  Исходные данные
 */
class PriceDTO extends AbstractDTO
{
    /**
     * Создать экземпляр цены.
     *
     * @param string $id        Идентификатор цены
     * @param float  $value     Значение цены
     * @param mixed  $original  Исходные данные
     */
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $id = null,

        #[Assert\Type('float')]
        #[Assert\PositiveOrZero]
        public $value = 0.0,

        #[Assert\NotBlank]
        public $original = null
    ) {}
}
