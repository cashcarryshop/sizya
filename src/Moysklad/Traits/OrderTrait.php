<?php
/**
 * Перечисление доступных знаков сравнения
 * для фильтров МойСклад
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

use CashCarryShop\Sizya\Moysklad\Enums\Order;

/**
 * Перечисление доступных знаков сравнения
 * для фильтров МойСклад
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait OrderTrait
{
    /**
     * Сортировка
     *
     * @var array<string, Order>
     */
    protected array $order = [];

    /**
     * Установить сортировку
     *
     * @param string       $name  Название поля по которому сортировать
     * @param string|Order $order Метод сортировки (DESC, ASC)
     *
     * @return static
     */
    public function order(string $name, string|Order $order = Order::DESC): static
    {
        $this->order[$name] = is_string($order)
            ? Order::from(strtolower($order))
            : $order;

        return $this;
    }
}
