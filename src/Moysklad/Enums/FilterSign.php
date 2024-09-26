<?php declare(strict_types=1);
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

namespace CashCarryShop\Sizya\Moysklad\Enums;

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
enum FilterSign: string
{
    case EQ         = '=';
    case NEQ        = '!=';
    case GT         = '>';
    case LT         = '<';
    case GTE        = '>=';
    case LTE        = '<=';
    case LIKE       = '~';
    case PREFIX     = '~=';
    case POSTFIX    = '=~';
}
