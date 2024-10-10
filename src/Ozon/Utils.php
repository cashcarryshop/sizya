<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Ozon;

use CashCarryShop\Sizya\DTO\DTOInterface;

/**
 * Класс с методами-утилитами для работы с Ozon.
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class Utils
{
    /**
     * Преобразовать дату из Ozon в `Y-m-d\TH:i:sp`.
     *
     * @param string $date Дата
     *
     * @return string|false
     */
    public static function dateToUtc(string $date): string|false
    {
        $date = \date_create_from_format('Y-m-d\TH:i:s.up', $date);

        return $date ? $date->format(DTOInterface::DATE_FORMAT) : $date;
    }

    /**
     * Преобразовать дату из `Y-m-d\TH:i:sp` в `Y-m-d\TH:i:s.up`.
     *
     * @param string $date Дата
     *
     * @return string
     */
    public static function dateToOzon(string $date): string
    {
        return \date_create_from_format(DTOInterface::DATE_FORMAT, $date)
            ->format('Y-m-d\TH:i:s.up');
    }
}
