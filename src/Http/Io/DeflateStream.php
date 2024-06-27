<?php
/**
 * Поток, с возможностью декодирования gzip
 *
 * PHP version 8
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Http\Io;

use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\StreamWrapper;
use Psr\Http\Message\StreamInterface;

/**
 * Поток, с возможностью декодирования gzip
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
final class DeflateStream implements StreamInterface
{
    use StreamDecoratorTrait;

    /**
     * Поток
     *
     * @var StreamInterface
     */
    private StreamInterface $stream;

    /**
     * Создание экземпляра закодированного потока
     *
     * @param StreamInterface $stream Поток
     * @param array           $params Параметры
     */
    public function __construct(StreamInterface $stream, array $params = [])
    {
        $resource = StreamWrapper::getResource($stream);
        stream_filter_append($resource, 'zlib.deflate', STREAM_FILTER_WRITE, $params);
        $this->stream = new Stream($resource);
    }
}
