<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya
 *
 * PHP version 8
 *
 * @category Stocks
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya;

use CashCarryShop\Sizya\DTO\StockDTO;

/**
 * Интерфейс с методами для получения остатков
 *
 * @category Stocks
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface StocksGetterInterface
{
    /**
     * Получить остатки товаров
     *
     * @see StockDTO
     *
     * @return StockDTO[]
     */
    public function getStocks(): array;
}
