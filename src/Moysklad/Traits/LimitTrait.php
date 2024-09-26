<?php declare(strict_types=1);
/**
 * Трейт с реализацией для сборки ограничения
 * по количеству элементов МойСклад
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
 * Трейт с реализацией для сборки ограничения
 * по количеству элементов МойСклад
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait LimitTrait
{
    /**
     * Лимит
     *
     * @var ?int
     */
    protected ?int $limit = null;

    /**
     * Установить лимит
     *
     * @param ?int $limit Значение лимита
     *
     * @return static
     */
    public function limit(?int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }
}
