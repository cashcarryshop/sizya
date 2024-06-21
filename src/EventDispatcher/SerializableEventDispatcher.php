<?php
/**
 * Отправитель по-умаолчанию
 *
 * PHP version 8
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\EventDispatcher;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CashCarryShop\Sizya\Utils;

/**
 * Отправитель по-умаолчанию
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class SerializableEventDispatcher implements EventDispatcherInterface
{
    /**
     * Основной диспетчер
     *
     * @var EventDispatcherInterface
     */
    public readonly EventDispatcherInterface $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        return $this->dispatcher->dispatch($event, $eventName);
    }

    public function getListeners(?string $eventName = null): array
    {
        return $this->dispatcher->getListeners($eventName);
    }

    public function getListenerPriority(string $eventName, callable|array $listener): ?int
    {
        return $this->dispatcher->getListenerPriority($eventName, $listener);
    }

    public function hasListeners(?string $eventName = null): bool
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * Завернуть обработчики для сериализации
     *
     * @param callable|array $listener Обработчик(и)
     *
     * @var callable|array
     */
    private function _wrapForSerialization(callable|array $listener): callable|array
    {
        if (is_array($listener)) {
            return array_map(
                fn ($listener) => Utils::getSerializableCallable($listener),
                $listener
            );
        }

        return Utils::getSerializableCallable($listener);
    }

    public function addListener(string $eventName, callable|array $listener, int $priority = 0): void
    {
        $this->dispatcher->addListener(
            $eventName, $this->_wrapForSerialization($listener), $priority
        );
    }

    public function removeListener(string $eventName, callable|array $listener): void
    {
        $this->dispatcher->removeListener(
            $eventName, $this->_wrapForSerialization($listener)
        );
    }

    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        $this->dispatcher->addSubscriber($subscriber);
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber): void
    {
        $this->dispatcher->removeSubscriber($subscriber);
    }
}
