<?php
declare(ticks=1);
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
use GuzzleHttp\Promise\Promise;

/**
 * Интерфейс отправителя запросов
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class SerializableSender implements SenderInterface
{
    /**
     * Запросы
     *
     * @var array
     */
    public array $requests = [];

    /**
     * Создание Sender
     */
    public function __construct()
    {
        register_shutdown_function([$this, 'shutdown']);
    }

    /**
     * Отправить запрос
     *
     * @param RequestInterface $request Запрос
     *
     * @return PromiseInterface
     */
    public function sendRequest(RequestInterface $request): PromiseInterface
    {
        $this->requests[] = $some = new SomeDecorator($request);
        return $some->promise;
    }

    /**
     * Какие параметры сериализовывать
     *
     * @return array
     */
    public function __sleep()
    {
        return [];
    }

    /**
     * Делаем что-то по тикам
     *
     * @return void
     */
    public function shutdown(): void
    {
        $requests = $this->requests;
        $this->requests = [];
        foreach ($requests as $request) {
            var_dump(serialize($request));
        }
    }
}
