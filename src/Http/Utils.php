<?php
/**
 * Вспомогательный класс для работы с Http
 *
 * PHP version 8
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Http;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Вспомогательный класс для работы с Http
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class Utils
{
    /**
     * Получить JsonBody
     *
     * @param array|string|object|resource $content Контент
     *
     * @return Io\JsonBody
     */
    public static function getJsonBody(array|string|object $content): Io\JsonBody
    {
        return new Io\JsonBody(fopen(
            is_resource($content)
                ? $content
                : sprintf(
                    'data://text/plain,%s',
                    is_string($content) ? $content : json_encode($content)
                ), 'r'
        ));
    }

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
}
