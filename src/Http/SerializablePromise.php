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

use GuzzleHttp\Promise\PromiseInterface;
use Closure;

/**
 * Интерфейс отправителя запросов
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class SerializablePromise implements PromiseInterface
{
    /**
     * Promise
     *
     * @var PromiseInterface
     */
    private PromiseInterface $promise;

    /**
     * Создание экземлпляра Promise
     *
     * @param PromiseInterface $promise Promise
     */
    public function __construct(PromiseInterface $promise)
    {
        $this->promise = $promise;
    }

    /**
     * Установка обработчиков
     *
     * @param ?callable $onFulfilled Обработчик на заполнение Promise
     * @param ?callable $onRejected  Обработчик ошибки
     *
     * @return PromiseInterface
     */
    public function then(
        ?callable $onFulfilled = null,
        ?callable $onRejected = null
    ): PromiseInterface {
        return new static($this->promise->then(
            Utils::getSerializableCallable($onFulfilled),
            Utils::getSerializableCallable($onRejected)
        ));
    }

    /**
     * Установка обработчика на reject
     *
     * @param callable $onRejected Обработчик
     *
     * @return PromiseInterface
     */
    public function otherwise(callable $onRejected): PromiseInterface
    {
        return new static($this->promise->otherwise(
            Utils::getSerializableCallable($onRejected)
        ));
    }

    /**
     * Получить статус Promise
     *
     * @return string
     */
    public function getState(): string
    {
        return $this->promise->getState();
    }

    /**
     * Установить результат выполнения
     *
     * @param mixed $value Значение
     *
     * @return void
     */
    public function resolve($value): void
    {
        $this->promise->resolve($value);
    }

    /**
     * Установить причину ошибки
     *
     * @param mixed $reason Причина
     *
     * @return void
     */
    public function reject($reason): void
    {
        $this->promise->reject($reason);
    }

    /**
     * Закрыть Promise
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->promise->cancel();
    }

    /**
     * Ожидать выполнение
     *
     * @param bool $unwrap Развернуть ли результат Promise
     *
     * @return mixed
     */
    public function wait(bool $unwrap = true)
    {
        return $this->promise->wait($unwrap);
    }
}
