<?php
/**
 * Интерфейс фабрики Deferred
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

/**
 * Интерфейс фабрики Deferred
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class DeferredFactory implements DeferredFactoryInterface
{
    /**
     * Создать объект Deferred
     *
     * @param callable $canceller Обработчик закрытия Promise
     *
     * @return Deferred
     */
    public function createDeferred(?callable $canceller = null): Deferred
    {
        return new Deferred($canceller);
    }
}
