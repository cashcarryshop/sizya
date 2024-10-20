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

use CashCarryShop\Sizya\DTO\ProductDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;

/**
 * Интерфейс с методам для получения
 * товаров по штрихкодам.
 *
 * @category Products
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface ProductsGetterByBarcodesInterface
{
    /**
     * Получить товары по штрихкодам.
     *
     * Количество возвращаемых элементов должно
     * соответствовать переданным или быть больше.
     *
     * Если в $barcodes было передано 2 одинаковых значения,
     * должна быть ошибка ByErrorDTO::DUPLICATE
     * или ByErrorDTO::VALIDATION.
     *
     * @param array $barcodes Артикулы
     *
     * @see ProductDTO
     * @see ByErrorDTO
     *
     * @return array<int, ProductDTO|ByErrorDTO>
     */
    public function getProductsByBarcodes(array $barcodes): array;
}
