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

use React\Promise\PromiseInterface;
use Throwable;

/**
 * Интерфейс фабрики Deferred
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface DeferredInterface
{
    /**
     * Установить результат выполнения
     *
     * @param mixed $value Значение
     *
     * @return void
     */
    public function resolve(mixed $value): void;

    /**
     * Установить причину ошибки
     *
     * @param Throwable $reason Причина
     *
     * @return void
     */
    public function reject(Throwable $reason): void;

    /**
     * Получить PromiseInterface
     *
     * @return PromiseInterface
     */
    public function promise(): PromiseInterface;
}
