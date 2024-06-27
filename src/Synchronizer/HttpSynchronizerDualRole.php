<?php
/**
 * Элемент синхронизации, взаимодействующий с протоколом Http
 *
 * PHP version 8
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Synchronizer;

use CashCarryShop\Sizya\Http\SenderInterface;
use CashCarryShop\Sizya\Http\Sender;
use CashCarryShop\Sizya\Http\PromiseResolverInterface;
use CashCarryShop\Sizya\Http\PromiseResolver;
use CashCarryShop\Sizya\Http\PoolInterface;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;

/**
 * Элемент синхронизации, взаимодействующий с протоколом Http
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class HttpSynchronizerDualRole extends AbstractSynchronizerDualRole
{
    /**
     * Отправитель запросов
     *
     * @var SenderInterface
     */
    protected SenderInterface $sender;

    /**
     * Promise resolver
     *
     * @var PromiseResolverInterface
     */
    protected PromiseResolverInterface $promiseResolver;

    /**
     * Установить отправителя запросов
     *
     * @param SenderInterface $sender Отправитель
     *
     * @return static
     */
    final public function withSender(SenderInterface $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * Получить отправитель запросов
     *
     * @return SenderInterface
     */
    final public function getSender(): SenderInterface
    {
        return $this->sender ??= new Sender;
    }

    /**
     * Установить Promise resolver
     *
     * @param PromiseResolverInterface $resolver Promise resolver
     *
     * @return static
     */
    final public function withPromiseResolver(
        PromiseResolverInterface $resolver
    ): static {
        $this->promiseResolver = $resolver;
        return $this;
    }

    /**
     * Получить Promise resolver
     *
     * @return PromiseResolverInterface
     */
    final public function getPromiseResolver(): PromiseResolverInterface
    {
        return $this->promiseResolver ??= new PromiseResolver;
    }

    /**
     * Отправить запрос
     *
     * @param RequestInterface $request Запрос
     *
     * @return PromiseInterface
     */
    final public function sendRequest(RequestInterface $request): PromiseInterface
    {
        return $this->getSender()->sendRequest($request);
    }

    /**
     * Использовать метод Pool отправителя
     *
     * @param iterable<RequestInterface> $requests Запросы
     * @param int                        $limit    Ограничение
     *
     * @return PoolInterface
     */
    final public function pool(iterable $requests, int $limit = 25): PoolInterface
    {
        return $this->getSender()->pool($requests, $limit);
    }
}
