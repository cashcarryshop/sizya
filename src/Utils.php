<?php
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Helpers
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya;

use CashCarryShop\Sizya\DTO\ByErrorDTO;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use function
    array_map,
    asort,
    ksort,
    reset,
    key,
    current,
    next;

/**
 * Набор вспомогательных методов для пакета.
 *
 * @category Helpers
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class Utils
{
    /**
     * На основе переданных данных сопоставить результаты.
     *
     * @param array<S>                  $values  Значения
     * @param array                     $results Результаты выполнения
     * @param callable(&array, mixed):T $setDto  Функция для получения dto по результатам
     * @param callable(T|ByErrorDTO):S  $pluck   Вытащить значения по полю из поиска
     * @param int                       $size    Размер чанка
     *
     * @return array<T|ByErrorDTO>
     */
    public static function mapResults(
        array    $values,
        array    $results,
        callable $setDto,
        callable $pluck,
        int      $size
    ): array {
        $items = [];
        foreach ($results as $idx => $result) {
            // Проверяем есть ли ошибки, если да, вместо
            // их вывода добавляем ErrorDTO в результат
            // выполнения.
            if ($result['state'] === 'fulfilled') {
                if ($result['value'] instanceof ResponseInterface) {
                    if ($result['value']->getCode() < 300) {
                        try {
                            $setDto($items, $result['value']);
                            continue;
                        } catch (Throwable $throwable) {
                            $type   = ByErrorDTO::INTERNAL;
                            $reason = $throwable;
                        }
                    } else {
                        $type   = ByErrorDTO::HTTP;
                        $reason = $result['value'];
                    }
                } else {
                    try {
                        $setDto($items, $result['value']);
                        continue;
                    } catch (Throwable $throwable) {
                        $type   = ByErrorDTO::INTERNAL;
                        $reason = $throwable;
                    }
                }
            } else {
                if ($result['reason'] instanceof RequestException) {
                    $type   = ByErrorDTO::HTTP;
                    $reason = $result['reason']->getResponse();
                } else if ($result['reason'] instanceof Throwable) {
                    $type   = ByErrorDTO::INTERNAL;
                    $reason = $result['reason'];
                } else {
                    $type   = ByErrorDTO::UNDEFINED;
                    $reason = $result['reason'];
                }
            }

            // Если ошибки есть
            for ($i = 0; $i < $size; ++$i) {
                $items[] = ByErrorDTO::fromArray([
                    'value'  => $values[$i * $idx],
                    'type'   => $type,
                    'reason' => $reason
                ]);
            }
        }

        // Вытягиваем значения, по которым был
        // произведен поиск из полученных элементов,
        // сортируем их, чтобы они шли в одинаковой
        // последовательности с исходными значениями,
        // устанавливаем на результаты, которые не
        // были найдены, ошибку ByErrorDTO::NOT_FOUND.
        // На выходе возвращаем полученные элементы
        // в той-же последовательности, в которой
        // были переданы значения для поиска.

        $filtered = array_map($pluck, $items);

        asort($values,   SORT_ASC);
        asort($filtered, SORT_ASC);

        $result = [];

        reset($filtered);
        foreach ($values as $idx => $value) {
            $itemIndex = key($filtered);

            if ($itemIndex === null
                || current($filtered) !== $value
            ) {
                $result[$idx] = ByErrorDTO::fromArray([
                    'value' => $value,
                    'type'  => ByErrorDTO::NOT_FOUND
                ]);

                continue;
            }

            $result[$idx] = $items[$itemIndex];
            next($filtered);
        }
        unset($filtered);
        unset($items);

        ksort($result);

        return $result;
    }
}
