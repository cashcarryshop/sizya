<?php
/**
 * Трейт с реализацией для сборки параметров МойСклад
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

use CashCarryShop\Sizya\Moysklad\Utils;

/**
 * Трейт с реализацией для сборки параметров МойСклад
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait ParamTrait
{
    /**
     * Параметры
     *
     * @var array<string, string|bool>
     */
    protected array $params = [];

    /**
     * Установить параметр
     *
     * @param string           $name  Название параметра
     * @param string|bool|null $value Значение
     *
     * @return static
     */
    public function param(string $name, string|bool|null $value = null): static
    {
        $this->params[$name] = Utils::prepareQueryValue($value);
        return $this;
    }
}
