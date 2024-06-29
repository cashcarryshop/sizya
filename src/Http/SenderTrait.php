<?php
/**
 * Основной трейт с реализацией SenderInterface
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
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Основной трейт с реализацией SenderInterface
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait SenderTrait
{
    /**
     * Используемый клиент
     *
     * @var ClientInterface
     */
    public readonly ClientInterface $client;

    /**
     * Создать экземпляр Sender
     *
     * @param ?ClientInterface $client Клиент
     */
    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client;
    }

    /**
     * Отправить запрос
     *
     * @param RequestInterface $request Запрос
     *
     * @return PromiseInterface
     */
    public function send(RequestInterface $request): PromiseInterface
    {
        return $this->client->sendAsync($request);
    }

    /**
     * Отправить запросы одновременно внутри Pool,
     * с ограничением на количество одновременно
     * выполняемых запросоы
     *
     * @param iterable<RequestInterface> $requests Запросы
     * @param int                        $limit    Ограничение Pool-а
     *
     * @return PoolInterface
     */
    public function pool(iterable $requests, int $limit = 25): PoolInterface
    {
        return new Pool($this, $requests, $limit);
    }
}
