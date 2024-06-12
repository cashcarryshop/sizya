<?php
/**
 * Трейт с методами для взаимодействия с Deferred
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
 * Трейт с методами для взаимодействия с Deferred
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
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
    public function withDeferredFactory(DeferredFactoryInterface $factory): static
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
        return $this->deferredFactory ??= new DeferredFactory;
    }

    /**
     * Получить Deferred
     *
     * @param callable $canceller Обработчик закрытия Promise
     *
     * @return DeferredInterface
     */
    public function deferred(?callable $canceller = null): DeferredInterface
    {
        return $this->getDeferredFactory()->createDeferred($canceller);
    }
}
