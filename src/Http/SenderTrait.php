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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;

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
     * Обработчик
     *
     * @var CurlMultiHandler
     */
    public readonly CurlMultiHandler $curl;

    /**
     * Создать экземпляр Sender
     *
     * @param ?ClientInterface $client Клиент
     *
     * @internal
     */
    public function __construct(?ClientInterface $client = null)
    {
        $this->curl ??= new CurlMultiHandler;
        $handler = HandlerStack::create($this->curl);
        $this->client = $client ?? new Client(['handler' => $handler]);
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
        try {
            return $promise = $this->client->sendAsync($request);
        } finally {
            while ($promise->getState() === $promise::PENDING) {
                $this->curl->tick();
            }
        }
    }
}
