<?php
/**
 * Абстрактный класс потока, внутри которого
 * преобразовываются данные
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

/**
 * Абстрактный класс потока, внутри которого
 * преобразовываются данные
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class JsonBody extends Stream
{
    /**
     * Конвертировать в array
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
        json_decode($this->getContents(), false, 512, JSON_THROW_ON_ERROR);
    }
}
