<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Moysklad\Traits;

/**
 * Трейт с реализацией сборки отступа для МойСклад.
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait OffsetTrait
{
    /**
     * Отступ
     *
     * @var ?int
     */
    protected ?int $offset = null;

    /**
     * Установить лимит
     *
     * @param ?int $offset Значение лимита
     *
     * @return static
     */
    public function offset(?int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }
}
