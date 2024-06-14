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

namespace CashCarryShop\Sizya\Moysklad\Io;

use CashCarryShop\Sizya\Http\Io\JsonBody as DefaultJsonBody;

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
class JsonBody extends DefaultJsonBody
{
    /**
     * Получить декодированные данные
     *
     * @return string
     */
    public function getContents(): string
    {
        $content = parent::getContents();
        if ($decoded = @gzdecode($content)) {
            return $decoded;
        }

        return $content;
    }
}
