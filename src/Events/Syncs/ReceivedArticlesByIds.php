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
class ReceivedArticlesByIds
{
    /**
     * Связи ([key] assortmentId -> [value] article)
     *
     * @var array
     */
    public readonly array $relations;

    /**
     * Создание события
     *
     * @param array $relations Связи
     */
    public function __construct(array $relations)
    {
        $this->relations = $relations;
    }
}
