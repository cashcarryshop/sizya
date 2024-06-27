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

use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Интерфейс отправителя запросов
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface SenderInterface
{
    /**
     * Отправить запрос
     *
     * @param RequestInterface $request Запрос
     *
     * @return PromiseInterface
     */
    public function sendRequest(RequestInterface $request): PromiseInterface;

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
    public function pool(iterable $requests, int $limit = 25): PoolInterface;
}
