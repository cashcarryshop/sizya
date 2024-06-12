<?php
/**
 * Вспомогательный класс для работы с Http
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

use Psr\Http\Message\StreamInterface;
use React\Http\Io\ReadableBodyStream;
use React\Promise\PromiseInterface;

/**
 * Вспомогательный класс для работы с Http
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class Utils
{
    /**
     * Дождаться когда тело ответа
     * полностью заполниться
     *
     * @param DeferredInterface $deferred Фабрика Promise
     * @param StreamInterface   $body     Тело запроса
     *
     * @return PromiseInterface
     */
    public static function waitFill(
        DeferredInterface $deferred,
        StreamInterface  $body
    ): PromiseInterface {
        if ($body instanceof ReadableBodyStream) {
            $buffer = '';

            $body->on('data', function ($chunk) use (&$buffer) {
                $buffer .= $chunk;
            });

            $body->on('error', fn ($reason) => $deferred->reject($reason));
            $body->on('close', function () use (&$buffer, $deferred) {
                $deferred->resolve($buffer);
            });

            return $deferred->promise();
        }

        $deferred->resolve($body->getContents());

        return $deferred->promise();
    }

    /**
     * Получить JsonBody
     *
     * @param array|string|object $content Контент
     *
     * @return Io\JsonBody
     */
    public static function getJsonBody(array|string|object $content): Io\JsonBody
    {
        return new Io\JsonBody(
            is_string($content)
                ? $content
                : json_encode($content)
        );
    }

    /**
     * Обработать значение для query
     *
     * @param string|bool|null $value Значение
     *
     * @return string
     */
    public static function prepareQueryValue(string|bool|null $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return $value ?? '';
    }
}
