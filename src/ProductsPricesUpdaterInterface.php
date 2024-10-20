<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Products
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya;

use CashCarryShop\Sizya\DTO\ProductPricesDTO;
use CashCarryShop\Sizya\DTO\ProductPricesUpdateDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;

/**
 * Интерфейс с методам для обновления цен товаров.
 *
 * @category Products
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface ProductsPricesUpdaterInterface
{
    /**
     * Обновить цены товаров.
     *
     * Количество возвращаемых элементов должно
     * соответствовать переданным.
     *
     * Если в $productsPrices было передано 2 одинаковых значения,
     * должна быть ошибка ByErrorDTO::DUPLICATE
     * или ByErrorDTO::VALIDATION.
     *
     * @param ProductPricesUpdateDTO[] $productsPrices Цены товаров
     *
     * @return array<int, ProductPricesDTO|ByErrorDTO>
     */
    public function updateProductsPrices(array $productsPrices): array;
}
