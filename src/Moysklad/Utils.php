<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
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

use CashCarryShop\Sizya\DTO\DTOInterface;
use CashCarryShop\Sizya\Utils as SizyaUtils;
use GuzzleHttp\Promise\Utils as PromiseUtils;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use DateTimeZone;
use RuntimeException;

/**
 * Набор вспомогательных методов для МойСклад.
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
        if (\is_bool($value)) {
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
        $exp = \explode('/', $meta['href']);
        return \end($exp);
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

        $datetime = \date_create_from_format($uFormat, $date, $uTimezone);

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

        $datetime = \date_create_from_format($mFormat1, $date, $mTimezone);
        $datetime = $datetime ? $datetime : \date_create_from_format(
            $mFormat2, $date, $mTimezone
        );

        return $datetime->setTimezone($uTimezone)->format($uFormat);
    }

    /**
     * Разделить массив со строками по чанкам, каждый
     * из которых не должен превышать размера $size.
     *
     * @param array<string> $array      Массив
     * @param int           $size       Размер в байтах
     * @param int           $additional Доп. размер каждого элемента
     * @param int           $maxLength  Максимальная длинна чанка
     *
     * @return array<array> Массив с чанками
     * @throws RuntimeException
     */
    public static function chunkBySize(
        array $array,
        int   $size = 6144,
        int   $additional = 0,
        int   $maxLength  = 0,
    ): array {
        $chunks = [];
        $currentChunkSize = 0;
        $currentChunk = [];

        foreach ($array as $idx => $item) {
            $itemSize = \mb_strlen($item, '8bit') + $additional;

            if ($itemSize > $size) {
                throw new RuntimeException(
                    sprintf(
                        'Item with key [%s] have size more then [%d], expected less or equal',
                        $idx,
                        $size
                    )
                );
            }

            if ($currentChunkSize + $itemSize > $size
                || (
                    $maxLength > 0
                        && \count($currentChunk) === $maxLength
                )
            ) {
                $chunks[]         = $currentChunk;
                $currentChunk     = [];
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

    /**
     * Получить данные по фильтрам.
     *
     * @param string            $filter    Название фильтра
     * @param array             $values    Значения для фильтра
     * @param RequestBuilder    $builder   Конструктор запросов
     * @param callable          $send      Функция отправки запросов
     * @param callable(R):array $getData   Функция для получения необходимых данных
     * @param int               $maxLength Максимальная длинна чанка
     *
     * @return PromiseInterface<DTOInterface|ByErrorDTO>
     */
    public static function getByFilter(
        string         $filter,
        array          $values,
        RequestBuilder $builder,
        callable       $send,
        callable       $getData,
        int            $maxLength = 0
    ): PromiseInterface {
        $promises = [];
        $addSize  = \mb_strlen($filter, '8bit');
        $chunks   = static::chunkBySize(
            $values,
            6144 + $addSize,
            $addSize,
            $maxLength
        );
        unset($additionalSize);
        unset($chunkSize);
        unset($values);

        foreach ($chunks as $chunk) {
            $clone = clone $builder;

            foreach ($chunk as $value) {
                $clone->filter($filter, $value);
            }

            $promises[] = $send($clone->build('GET'));
        }

        return PromiseUtils::settle($promises)->then(
            static fn ($results) => SizyaUtils::mapResults(
                $chunks,
                $results,
                $getData
            )
        );
    }

    /**
     * Просто получить данные
     *
     * @param RequestBuilder               $builder    Конструктор запросов
     * @param int                          $limit      Ограничение по количеству элементов
     * @param int                          $chunkLimit Ограничение по количеству для чанка
     * @param callable(RequestInterface):R $send       Отправить запрос
     * @param callable(R):T[]              $getDtos    Получить dto
     *
     *
     * @return T[]
     */
    public static function getAll(
        RequestBuilder $builder,
        int            $limit,
        int            $chunkLimit,
        callable       $send,
        callable       $getDtos
    ): array {
        $offset = 0;

        $counter   = $limit;
        $maxOffset = $limit;

        $items = [];

        do {
            $clone = (clone $builder)
                ->offset($offset)
                ->limit($counter > $chunkLimit ? $chunkLimit : $counter);

            $items = \array_merge(
                $items,
                $chunk = $getDtos($send($clone->build('GET'))->wait())
            );

            $offset  += $chunkLimit;
            $counter -= $chunkLimit;
        } while (\count($chunk) === $chunkLimit && $counter > 0);

        return $items;
    }
}
