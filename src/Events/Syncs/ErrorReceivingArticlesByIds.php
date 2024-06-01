<?php
/**
 * Событие, возникающее когда произошла
 * ошибка во время получения артикулов
 * по идентификаторам ассортимента
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Events\Syncs;

use CashCarryShop\Sizya\Events\Error;
use Throwable;

/**
 * Событие, возникающее когда произошла
 * ошибка во время получения артикулов
 * по идентификаторам ассортимента
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class ErrorReceivingArticlesByIds extends Error
{
    /**
     * Идентификаторы ассортимента (assortmentId)
     *
     * @var array
     */
    public readonly array $ids;

    /**
     * Создание события
     *
     * @param array     $ids       Идентификаторы
     * @param Throwable $exception Исключение
     */
    public function __construct(array $ids, Throwable $exception)
    {
        $this->ids = $ids;
        parent::__construct($exception);
    }
}
