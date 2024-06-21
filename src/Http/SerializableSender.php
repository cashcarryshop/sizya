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

namespace CashCarryShop\Sizya\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;

/**
 * Отправитель по-умаолчанию
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class SerializableSender implements SenderInterface
{
    use InteractsWithPromise;

    /**
     * Запросы
     *
     * @var array
     */
    public array $requests = [];

    /**
     * Обработчик
     *
     * @var CurlMultiHandler
     */
    public readonly CurlMultiHandler $curl;

    /**
     * Клиент
     *
     * @var ClientInterface
     */
    public readonly ClientInterface $client;

    /**
     * Создать экземпляр Sender
     */
    public function __construct()
    {
        $this->curl ??= new CurlMultiHandler;
        $handler = HandlerStack::create($this->curl);
        $this->client = new Client(['handler' => $handler]);
    }

    /**
     * Какие свойства сериализовывать
     *
     * @return array
     */
    public function __sleep()
    {
        return ['requests'];
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
        $this->requests[] = [
            $request,
            $promise = new SerializablePromise($this->promise())
        ];

        return $promise;
    }
}
