<?php
/**
 * Фабрика Promise
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
use React\Promise\Promise;
use Throwable;

/**
 * Фабрика Promise
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class Deferred implements DeferredInterface
{
    /**
     * Promise
     *
     * @var Promise
     */
    protected Promise $promise;

    /**
     * Функция для установки результата в Promise
     *
     * @var callable
     */
    protected $resolveFn;

    /**
     * Функция для установки причины ошибки в Promise
     *
     * @var callable
     */
    protected $rejectFn;

    /**
     * Создать экземпляр класса
     *
     * @param ?callable $canceller Функция на завернешние Promise
     */
    public function __construct(?callable $canceller = null)
    {
        $this->promise = new Promise(
            function ($resolve, $reject) {
                $this->resolveFn = $resolve;
                $this->rejectFn = $reject;
            }, $canceller
        );
    }

    /**
     * Установить результат выполнения
     *
     * @param mixed $value Значение
     *
     * @return void
     */
    public function resolve(mixed $value): void
    {
        call_user_func($this->resolveFn, $value);
    }

    /**
     * Установить причину ошибки
     *
     * @param Throwable $reason Причина
     *
     * @return void
     */
    public function reject(Throwable $reason): void
    {
        call_user_func($this->rejectFn, $reason);
    }

    /**
     * Получить PromiseInterface
     *
     * @return PromiseInterface
     */
    public function promise(): PromiseInterface
    {
        return $this->promise;
    }
}
