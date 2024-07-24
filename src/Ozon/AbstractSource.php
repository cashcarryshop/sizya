<?php
/**
 * Абстрактный класс источников для
 * синхронизаций Ozon Seller Api.
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

use CashCarryShop\Synchronizer\SynchronizerSourceInterface;

/**
 * Абстрактный класс источников для
 * синхронизаций Ozon Seller Api.
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class AbstractSource implements SynchronizerSourceInterface
{
    use InteractsWithOzonSeller;
}
