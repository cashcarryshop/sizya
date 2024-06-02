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
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Events\Syncs;

/**
 * Событие, возникающее когда произошла
 * ошибка во время получения артикулов
 * по идентификаторам ассортимента
 *
 * PHP version 8
 *
 * @category Events
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class ReceivingArticlesByIds
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
     * @param array $ids Идентификаторы
     */
    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }
}
