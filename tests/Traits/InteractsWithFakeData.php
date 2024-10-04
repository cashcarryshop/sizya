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

use CashCarryShop\Sizya\DTO\DTOInterface;


/**
 * Трейт с методами для генерации фейковых данных.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait InteractsWithFakeData
{
        /**
     * Сгенерировать guidv4
     *
     * @param ?string $data Битовые данные длинной 128 битов
     *
     * @return string
     */
    protected static function guidv4(?string $data = null): string
    {
        // Generate 16 bytes (128 bits) of random data
        // or use the data passed into the function.
        $data = $data ?? \random_bytes(16);
        assert(\strlen($data) == 16);

        // Set version to 0100
        $data[6] = \chr(\ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = \chr(\ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return \vsprintf('%s%s-%s-%s-%s-%s%s%s', \str_split(\bin2hex($data), 4));
    }

    /**
     * Сгенерировать фейковый артикул.
     *
     * @param string $prefix Префикс
     * @param ?int   $index  Индекс
     * @param int    $length Длина
     *
     * @return string
     */
    protected static function fakeArticle(
        string $prefix = 'CCS',
        ?int   $index  = null,
        int    $length = 5
    ): string {
        return $prefix . \str_pad(
            $index ?? (string) \random_int(0, 1000),
            $length,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Сгенерировать рандомную строку.
     *
     * @param int $length Длина строки
     *
     * @return string
     */
    protected static function fakeString(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = \strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[\random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Сгенерировать рандомную дату для dto.
     *
     * @return string
     */
    protected static function fakeDtoDate(): string
    {
        return \date(DTOInterface::DATE_FORMAT, \mt_rand(1, \time()));
    }

    /**
     * Сгенерировать фейковую дату в формате Y-m-d H:i:s.
     *
     * @return string
     */
    protected static function fakeDate(): string
    {
        return \date('Y-m-d H:i:s', \mt_rand(1, \time()));
    }
}
