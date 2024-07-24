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
}
