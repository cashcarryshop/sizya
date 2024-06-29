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
use CashCarryShop\Sizya\Http\PromiseAggregatorInterface;
use CashCarryShop\Sizya\Http\PromiseAggregator;
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
     * Promise Aggregator
     *
     * @var PromiseAggregatorInterface
     */
    protected PromiseAggregatorInterface $promiseAggregator;

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
     * Установить Promise Aggregator
     *
     * @param PromiseAggregatorInterface $aggregator Promise Aggregator
     *
     * @return static
     */
    final public function withPromiseAggregator(
        PromiseAggregatorInterface $aggregator
    ): static {
        $this->promiseAggregator = $aggregator;
        return $this;
    }

    /**
     * Получить Promise Aggregator
     *
     * @return PromiseAggregatorInterface
     */
    final public function getPromiseAggregator(): PromiseAggregatorInterface
    {
        return $this->promiseAggregator ??= new PromiseAggregator;
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
        return $this->getSender()->send($request);
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
