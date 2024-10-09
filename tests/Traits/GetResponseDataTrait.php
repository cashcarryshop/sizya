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

/**
 * Трейт с методом для получения данных ответов
 * от стороннего api.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see OrdersGetterByAdditionalInterface
 */
trait GetResponseDataTrait
{
    /**
     * Получить данные для response метода api.
     *
     * Останавливает тестирование если данные
     * по $key не существуют.
     *
     * @param string $key Ключ для получения ответа от api
     *
     * @return ?array
     */
    protected static function getResponseData(string $key): ?array
    {
        $data = \json_decode(
            \file_get_contents(ROOT . '/responses.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (\array_key_exists($key, $data)) {
            return $data[$key];
        }

        static::markTestSkipped(sprintf('Response data by [%s] key not found', $key));
    }
}
