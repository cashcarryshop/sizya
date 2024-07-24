<?php
/**
 * Абстрактный класс сущностей для
 * синхронизаций МойСклад.
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

use CashCarryShop\Synchronizer\SynchronizerDualRoleInterface;

/**
 * Абстрактный класс сущностей для
 * синхронизаций МойСклад.
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class AbstractEntity implements SynchronizerDualRoleInterface
{
    use InteractsWithMoysklad;
}
