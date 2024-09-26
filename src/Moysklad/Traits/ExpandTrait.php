<?php declare(strict_types=1);
/**
 * Трейт для работы с разворачиванием
 * полей при запросе к МойСклад
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
 * Трейт для работы с разворачиванием
 * полей при запросе к МойСклад
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait ExpandTrait
{
    /**
     * Фильтры
     *
     * @var array<string>
     */
    protected array $expand = [];

    /**
     * Установить фильтр
     *
     * @param string $path Путь до поля которое необходимо развернуть
     *
     * @return static
     */
    public function expand(string $path): static
    {
        $this->expand[] = $path;
        return $this;
    }
}
