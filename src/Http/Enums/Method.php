<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Http\Enums;

/**
 * Перечисление доступных методов HTTP
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
enum Method: string
{
    case GET    = 'GET';
    case POST   = 'POST';
    case PATCH  = 'PATCH';
    case PUT    = 'PUT';
    case DELETE = 'DELETE';
}
