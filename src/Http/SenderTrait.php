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
use React\EventLoop\LoopInterface;
use React\EventLoop\Loop;
use React\Http\Client\Client as HttpClient;
use React\Promise\PromiseInterface;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;
use React\Stream\ReadableStreamInterface;
use React\Http\Io\ClientConnectionManager;

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
    use InteractsWithDeferred;

    /**
     * Создайте новый Sender по умолчанию, прикрепленного к данному циклу событий.
     *
     * ```php
     * $connector = new \React\Socket\Connector([], $loop);
     * $sender = \React\Http\Io\Sender::createFromLoop($loop, $connector);
     * ```
     *
     * @param LoopInterface           $loop      Цикл
     * @param ConnectorInterface|null $connector Коннектор
     *
     * @return static
     */
    public static function createFromLoop(
        LoopInterface $loop,
        ConnectorInterface $connector = null
    ): static {
        if ($connector === null) {
            $connector = new Connector([], $loop);
        }

        return new static(
            new HttpClient(
                new ClientConnectionManager($connector, $loop)
            )
        );
    }

    /**
     * Создать новый отправитель по-стандарту
     *
     * @param ?HttpClient $http Клиент
     *
     * @return static
     */
    public static function create(?HttpClient $http = null): static
    {
        return $http ? new static($http) : static::createFromLoop(Loop::get());
    }

    /**
     * Используемый клиент
     *
     * @var HttpClient
     */
    protected HttpClient $http;

    /**
     * Создать экземпляр Sender
     *
     * @param HttpClient $http Клиент
     *
     * @internal
     */
    public function __construct(HttpClient $http)
    {
        $this->http = $http;
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
        // support HTTP/1.1 and HTTP/1.0 only, ensured by `Browser` already
        assert(\in_array($request->getProtocolVersion(), ['1.0', '1.1'], true));

        $body = $request->getBody();
        $size = $body->getSize();

        if ($size !== null && $size !== 0) {
            // automatically assign a "Content-Length" request header if the body size is known and non-empty
            $request = $request->withHeader('Content-Length', (string)$size);
        } elseif ($size === 0 && \in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            // only assign a "Content-Length: 0" request header if the body is expected for certain methods
            $request = $request->withHeader('Content-Length', '0');
        } elseif ($body instanceof ReadableStreamInterface && $size !== 0 && $body->isReadable() && !$request->hasHeader('Content-Length')) {
            // use "Transfer-Encoding: chunked" when this is a streaming body and body size is unknown
            $request = $request->withHeader('Transfer-Encoding', 'chunked');
        } else {
            // do not use chunked encoding if size is known or if this is an empty request body
            $size = 0;
        }

        // automatically add `Authorization: Basic …` request header if URL includes `user:pass@host`
        if ($request->getUri()->getUserInfo() !== '' && !$request->hasHeader('Authorization')) {
            $request = $request->withHeader('Authorization', 'Basic ' . \base64_encode($request->getUri()->getUserInfo()));
        }

        $requestStream = $this->http->request($request);

        $deferred = $this->deferred(function ($_, $reject) use ($requestStream) {
            // close request stream if request is cancelled
            $reject(new \RuntimeException('Request cancelled'));
            $requestStream->close();
        });

        $requestStream->on('error', function($error) use ($deferred) {
            $deferred->reject($error);
        });

        $requestStream->on('response', function (ResponseInterface $response) use ($deferred, $request) {
            $deferred->resolve($response);
        });

        if ($body instanceof ReadableStreamInterface) {
            if ($body->isReadable()) {
                // length unknown => apply chunked transfer-encoding
                if ($size === null) {
                    $body = new ChunkedEncoder($body);
                }

                // pipe body into request stream
                // add dummy write to immediately start request even if body does not emit any data yet
                $body->pipe($requestStream);
                $requestStream->write('');

                $body->on('close', $close = function () use ($deferred, $requestStream) {
                    $deferred->reject(new \RuntimeException('Request failed because request body closed unexpectedly'));
                    $requestStream->close();
                });
                $body->on('error', function ($e) use ($deferred, $requestStream, $close, $body) {
                    $body->removeListener('close', $close);
                    $deferred->reject(new \RuntimeException('Request failed because request body reported an error', 0, $e));
                    $requestStream->close();
                });
                $body->on('end', function () use ($close, $body) {
                    $body->removeListener('close', $close);
                });
            } else {
                // stream is not readable => end request without body
                $requestStream->end();
            }
        } else {
            // body is fully buffered => write as one chunk
            $requestStream->end((string) $body);
        }

        return $deferred->promise();
    }
}
