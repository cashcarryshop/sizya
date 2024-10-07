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
 * DTO для отношений сущностей.
 *
 * @category DTO
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see DTOInterface
 *
 * @property string  $sourceId Идентификатор источника
 * @property string  $targetId Идентфикатор цели
 * @property ?string $testKey  Тестовый ключ для сопоставления данных
 */
class RelationDTO extends AbstractDTO
{
    /**
     * Создать экземпляр позиции
     *
     * @param string  $sourceId Идентификатор источника
     * @param string  $targetId Идентификатор цели
     * @param ?string $testKey  Тестовый ключ для сопоставления данных
     */
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $sourceId = null,

        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public $targetId = null,

        #[Assert\Type(['string', 'null'])]
        #[Assert\When(
            expression: 'value !== null',
            constraints: [new Assert\NotBlank]
        )]
        public $testKey = null
    ) {}
}
