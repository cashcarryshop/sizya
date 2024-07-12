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
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Stream;
use Throwable;

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
     * Получить поток
     *
     * @param mixed     $content Контент
     * @param ?callable $filter  Через какие фильтры провести контент
     *
     * @return StreamInterface
     */
    public static function getStream(
        mixed $content,
        ?callable $filter = null
    ): StreamInterface {
        if (is_a($content, StreamInterface::class)) {
            return $content;
        }

        if (is_string($content) && is_file($content)) {
            $content = file_get_contents($content);
        }

        if (is_resource($content)) {
            $content = stream_get_contents($content);
        }

        if ($filter) {
            $filtered = call_user_func($filter, $content);
            $content = $filtered ?? $content;
            unset($filtered);
        }

        if (!is_string($content)) {
            $content = serialize($content);
        }

        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write($content);
        $stream->rewind();
        return $stream;
    }

    /**
     * Получить поток для Json
     *
     * @param array|string|object|resource $content Контент
     *
     * @return Io\JsonStream
     */
    public static function getJsonStream(array|string|object $content): Io\JsonStream
    {
        return new Io\JsonStream(static::getStream($content, 'json_encode'));
    }

    /**
     * Получить декодированных из gzip поток
     *
     * @param array|string|object|resource $content Контент
     *
     * @return Io\InflateStream
     */
    public static function getInflateStream(
        array|string|object $content
    ): Io\InflateStream {
        return new Io\InflateStream(static::getStream($content));
    }

    /**
     * Получить кодированный поток в gzip
     *
     * @param array|string|object|resource $content Контент
     *
     * @return Io\DeflateStream
     */
    public static function getDeflateStream(
        array|string|object $content
    ): Io\DeflateStream {
        return new Io\DeflateStream(static::getStream($content, 'gzencode'));
    }

    /**
     * Развернуть одиночный результат
     * выполнения из `PromiseAggregator::settle`
     *
     * @param PromiseInterface $promise Promise
     *
     * @return PromiseInterface
     */
    public function unwrapSingleSettle(PromiseInterface $promise): PromiseInterface
    {
        return $promise->then(
            static fn ($results) => $results[0]['value'],
            static function ($aggregation) {
                $reason = $aggregation->getReason()[0]['reason'];
                if (is_a($reason, Throwable::class)) {
                    throw $reason;
                }

                return $reason;
            }
        );
    }

    /**
     * Распаковать результат выполнения из `PromiseAggregator::settle`,
     * исключая результаты `PromiseInterface::REJECTED` и возвращая
     * значения (value) из результатов `PromiseInterface::FULFILLED`
     *
     * @param PromiseInterface $promise Promise
     *
     * @return PromiseInterface
     */
    public function unwrapSettle(PromiseInterface $promise): PromiseInterface
    {
        return $promise->then(
            static fn ($results) => array_column(
                array_filter($results, static function ($result) {
                    return $result['state'] === PromiseInterface::FULFILLED;
                }),
                'value'
            )
        );
    }
}
