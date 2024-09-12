<?php
/**
 * Этот файл является частью пакета sizya
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Moysklad;

use CashCarryShop\Sizya\Http\Utils as HttpUtils;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use DateTimeZone;
use RuntimeException;

/**
 * Набор вспомогательных методов для МойСклад
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class Utils
{
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

    /**
     * Вытащить из метаданных guid элемента
     *
     * @param array $meta Метаданные
     *
     * @return string
     */
    public static function guidFromMeta(array $meta): string
    {
        $exp = explode('/', $meta['href']);
        return end($exp);
    }

    /**
     * Форматировать дату Формата UTC в МойСклад
     *
     * @param string $date Дата UTC
     *
     * @return string Дата в формате МойСклад
     */
    public static function dateToMoysklad(string $date): string
    {
        static $mTimezone;
        static $uTimezone;
        static $mFormat = 'Y-m-d H:i:s';
        static $uFormat = 'Y-m-d\TH:i:s\Z';

        $mTimezone ??= new DateTimeZone('Europe/Moscow');
        $uTimezone ??= new DateTimeZone('UTC');

        $datetime = date_create_from_format($uFormat, $date, $uTimezone);

        return $datetime->setTimezone($mTimezone)->format($mFormat);
    }

    /**
     * Форматировать дату Формата МойСклад в UTC
     *
     * То-есть форматирует из `Y-m-d H:i:s.v` в `Y-m-d\TH:i:s\Z`,
     * также конвертирует часовой пояс в UTC
     *
     * @param string $date Дата из МойСклад
     *
     * @return string Дата в UTC
     */
    public static function dateToUtc(string $date): string
    {
        static $mTimezone;
        static $uTimezone;
        static $mFormat1 = 'Y-m-d H:i:s.v';
        static $mFormat2 = 'Y-m-d H:i:s';
        static $uFormat = 'Y-m-d\TH:i:s\Z';

        $mTimezone ??= new DateTimeZone('Europe/Moscow');
        $uTimezone ??= new DateTimeZone('UTC');

        $datetime = date_create_from_format($mFormat1, $date, $mTimezone);
        $datetime = $datetime ? $datetime : date_create_from_format(
            $mFormat2, $date, $mTimezone
        );

        return $datetime->setTimezone($uTimezone)->format($uFormat);
    }

    /**
     * Разделить массив со строками по чанкам, каждый
     * из которых не должен превышать размера $size.
     *
     * @param array<string> $array      Массив
     * @param int           $size       Размер
     * @param int           $additional Доп. размер каждого элемента
     *
     * @return array<array> Массив с чанками
     * @throws RuntimeException
     */
    public static function chunkBySize(array $array, int $size = 3072, int $additional = 0): array
    {
        $size = $size * 1024;

        $chunks = [];
        $currentChunkSize = 0;
        $currentChunk = [];

        foreach ($array as $idx => $item) {
            $itemSize = mb_strlen($item, '8bit') + $additional;

            if ($itemSize > $size) {
                throw new RuntimeException(
                    sprintf(
                        'Item with key [%s] have size more then [%d], expected less or equal',
                        $idx,
                        $size
                    )
                );
            }

            if ($currentChunkSize + $itemSize > $size) {
                $chunks[] = $currentChunk;
                $currentChunk = [];
                $currentChunkSize = 0;
            }

            $currentChunk[$idx] = $item;
            $currentChunkSize += $itemSize;
        }

        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }
}
