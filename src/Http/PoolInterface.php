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

/**
 * Интерфейс Pool (бассейна) для запросов
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface PoolInterface
{
    /**
     * Создать экземпляр Pool
     *
     * @param SenderInterface            $sender   Отправитель запросов
     * @param iterable<RequestInterface> $requests Запросы
     * @param int                        $limit    Ограничение
     */
    public function __construct(
        SenderInterface $sender,
        iterable $requests,
        int $limit
    );

    /**
     * Получить все полученные Promise из Poool
     *
     * @return iterable<PromiseInterface>
     */
    public function getPromises(): iterable;
}
