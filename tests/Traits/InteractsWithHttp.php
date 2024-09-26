<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Tests\Traits;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\After;

/**
 * Трейт с методами для работы с http.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait InteractsWithHttp
{
    use GetResponseDataTrait;

    /**
     * Макет обработчика для клиента.
     *
     * @var ?MockHandler
     */
    protected static ?MockHandler $handler = null;

    #[BeforeClass]
    public static function setUpHttp(): void
    {
        static::$handler = new MockHandler;
    }

    #[After]
    protected function resetHttpHandler(): void
    {
        static::$handler->reset();
    }

    /**
     * Получить клиент с переданным handler-ом.
     *
     * @param callable $handler Обработчик
     *
     * @return Client
     */
    protected static function createHttpClient(callable $handler): Client
    {
        return new Client(['handler' => $handler]);
    }

    /**
     * Создать Response
     *
     * @param int   $code    Код статуса
     * @param array $headers Заголовки
     * @param mixed $body    Тело запроса
     *
     * @return Response
     */
    protected static function createResponse(
        int $code = 200,
        array $headers = [],
        mixed $body = null
    ): Response {
        return new Response(
            $code,
            $headers,
            $body,
        );
    }

    /**
     * Создать response с json телом.
     *
     * @param int   $code    Код статуса
     * @param array $headers Заголовки
     * @param array $body    Тело запроса
     *
     * @return Response
     */
    protected static function createJsonResponse(
        int $code = 200,
        array $headers = [],
        array $body = []
    ): Response {
        return static::createResponse(
            $code,
            $headers,
            \json_encode(
                $body,
                JSON_THROW_ON_ERROR,
                512
            )
        );
    }
}
