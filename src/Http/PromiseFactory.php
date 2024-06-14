<?php
/**
 * Интерфейс отправителя запросов
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

use GuzzleHttp\Promise\Promise;

/**
 * Интерфейс отправителя запросов
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class PromiseFactory implements PromiseFactoryInterface
{
    /**
     * Создать Promise
     *
     * @param ?callable $waitFn   Функция, вызывающаяся при вызове метода wait
     * @param ?callable $cancelFn Функция, вызывающаяся при cancel promise
     *
     * @return PromiseInterface
     */
    public function createPromise(
        ?callable $waitFn = null,
        ?callable $cancelFn = null
    ): Promise {
        return new Promise($waitFn ?? fn () => null, $cancelFn);
    }
}
