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
final class JsonStream implements StreamInterface
{
    use StreamDecoratorTrait;

    /**
     * Конвертировать в массив
     *
     * @return array
     */
    public function toArray(): array
    {
        return json_decode($this->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Конвертировать в объект
     *
     * @return array|object
     */
    public function toObject(): array|object
    {
        return json_decode($this->getContents(), false, 512, JSON_THROW_ON_ERROR);
    }
}
