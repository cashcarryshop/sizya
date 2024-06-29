<?php
/**
 * Интерфейс Pool (бассейна) для запросов
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

use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\EachPromise;

/**
 * Интерфейс Pool (бассейна) для запросов
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class Pool implements PoolInterface
{
    /**
     * Общий Promise для всех запросов
     *
     * @var EachPromise
     */
    private EachPromise $_each;

    /**
     * Промисы запросов
     *
     * @var iterable<PromiseInterface>
     */
    private iterable $_promises = [];

    /**
     * Создать экземпляр Pool
     *
     * @param SenderInterface            $sender   Клиент который отправляет запросы
     * @param iterable<RequestInterface> $requests Запросы
     * @param int                        $limit    Ограничение
     */
    public function __construct(
        SenderInterface $sender,
        iterable $requests,
        int $limit
    ) {
        foreach ($requests as $request) {
            $this->_promises[] = $sender->send($request);
        }

        $this->_each = new EachPromise($this->_promises, ['concurrency' => $limit]);
    }

    /**
     * Получить основной Promise
     *
     * @return PromiseInterface
     */
    public function promise(): PromiseInterface
    {
        return $this->_each->promise();
    }

    /**
     * Получить все полученные Promise из Poool
     *
     * @return iterable<PromiseInterface>
     */
    public function getPromises(): iterable
    {
        return $this->_promises;
    }
}
