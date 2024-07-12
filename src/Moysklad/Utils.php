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

namespace CashCarryShop\Sizya\Moysklad;

use CashCarryShop\Sizya\Http\Utils as HttpUtils;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use DateTimeZone;

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
class Utils extends HttpUtils
{
    /**
     * Обработать значение для query
     *
     * @param string|bool|null $value Значение
     *
     * @return string
     */
    public static function prepareQueryValue(string|bool|null $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return $value ?? '';
    }

    /**
     * Конвертировать дату и время в формат для МойСклад
     *
     * @param string        $format   Формат
     * @param string        $datetime Дата и время
     * @param ?DateTimeZone $timezone Часовой пояс времени
     *
     * @return string
     */
    public static function createFromFormat(
        string $format,
        string $datetime,
        ?DateTimeZone $timezone
    ): string {
        return date_create_from_format($format, $datetime, $timezone)
            ->setTimezone(new DateTimeZone('Europe/Moscow'))
            ->format('Y-m-d H:i:s');
    }
}
