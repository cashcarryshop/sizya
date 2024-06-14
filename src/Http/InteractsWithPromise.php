<?php
/**
 * Трейт с методами для взаимодействия с Promise
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

/**
 * Трейт с методами для взаимодействия с Promise
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait InteractsWithPromise
{
    /**
     * Фабрика Deferred
     *
     * @var PromiseFactoryInterface
     */
    protected PromiseFactoryInterface $promiseFactory;

    /**
     * Установить фабрику Promise
     *
     * @param PromiseFactoryInterface $factory Фабрика
     *
     * @return static
     */
    public function withPromiseFactory(PromiseFactoryInterface $factory): static
    {
        $this->promiseFactory = $factory;
        return $this;
    }

    /**
     * Получить фабрику Promise
     *
     * @return PromiseFactoryInterface
     */
    public function getPromiseFactory(): PromiseFactoryInterface
    {
        return $this->promiseFactory ??= new PromiseFactory;
    }

    /**
     * Получить Promise
     *
     * @return PromiseInterface
     */
    public function promise(): PromiseInterface
    {
        return $this->getPromiseFactory()->createPromise(...func_get_args());
    }
}
