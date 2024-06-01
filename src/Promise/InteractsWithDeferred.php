<?php
/**
 * Трейт для взаимодействия с Deferred
 *
 * PHP version 8
 *
 * @category Promise
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Promise;

use CashCarryShop\Promise\DeferredFactoryInterface;
use CashCarryShop\Promise\DeferredInterface;
use CashCarryShop\Promise\PromiseInterface;
use CashCarryShop\Promise\DeferredFactory;
use Throwable;

/**
 * Трейт для взаимодействия с Deferred
 *
 * @category Promise
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
trait InteractsWithDeferred
{
    /**
     * Фабрика Deferred
     *
     * @var DeferredFactoryInterface
     */
    protected DeferredFactoryInterface $deferredFactory;

    /**
     * Установить фабрику Deferred
     *
     * @param DeferredFactoryInterface $factory Фабрика
     *
     * @return static
     */
    public function withDeferredFactory(DeferredFactoryInterface $factory)
    {
        $this->deferredFactory = $factory;
        return $this;
    }

    /**
     * Получить фабрику Deferred
     *
     * @return DeferredFactoryInterface
     */
    public function getDeferredFactory(): DeferredFactoryInterface
    {
        if (isset($this->deferredFactory)) {
            return $this->deferredFactory;
        }

        return $this->withDeferredFactory(new DeferredFactory)->getDeferredFactory();
    }

    /**
     * Запустить процесс, который может быть отложенно выполнен
     *
     * Передается разрешитель (функция, замыкание), в который
     * передается DeferredInterface, а внутри необходимо,
     * используя методы DeferredInterface, устанавливать
     * полученные параметры, или выдавать ошибку
     *
     * @param callable $resolve Разрешитель
     *
     * @return PromiseInterface
     */
    public function deferred(callable $resolve): PromiseInterface
    {
        call_user_func(
            $resolve,
            $deferred = $this->getDeferredFactory()->createDeferred()
        );

        return $deferred->promise();
    }

    /**
     * Сделать отложенную задачу,
     * перехватив только ошибку
     *
     * @param callable $resolve Разрешитель
     *
     * @return PromiseInterface
     */
    public function resolveThrow(callable $resolve): PromiseInterface
    {
        return $this->deferred(function ($deferred) use ($resolve) {
            try {
                call_user_func($resolve, $deferred);
            } catch (Throwable $exception) {
                $deferred->reject($exception);
            }
        });
    }

    /**
     * Запустить отложенную задачу
     *
     * Сразу обрабатывает Promise, достаточно
     * передать функцию, из которой выходит
     * результат выполнения
     *
     * Самостоятельно отлавливает исключения
     *
     * @param callable $get Функция, откуда получаем данные
     *
     * @return PromiseInterface
     */
    public function resolve(callable $get): PromiseInterface
    {
        return $this->deferred(function ($deferred) use ($get) {
            try {
                $deferred->resolve(call_user_func($get));
            } catch (Throwable $exception) {
                $deferred->reject($exception);
            }
        });
    }
}
