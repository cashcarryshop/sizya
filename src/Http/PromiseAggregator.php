<?php
/**
 * Простая реализация PromiseAggregatorInterface
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

use GuzzleHttp\Promise\AggregateException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Promise\Each;

/**
 * Простая реализация PromiseAggregatorInterface
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class PromiseAggregator implements PromiseAggregatorInterface
{
    /**
     * Ожидает выполнения всех Promise и возвращает
     * массив с результатами их выполнения
     *
     * @param iterable<PromiseInterface> $promises Promise-ы с рабочим методом wait
     *
     * @return array
     * @throws Throwable
     */
    public function unwrap(iterable $promises): array
    {
        return Utils::unwrap($promises);
    }

    /**
     * По завершению выполнения Promise-ов вернет
     * результат их выполнения
     *
     * Если хоть 1 Promise был выполнен с ошибкой, то
     * отправится ошибка AggregateException
     *
     * @param iterable<PromiseInterface> $promises Promise-ы
     *
     * @return PromiseInterface
     */
    public function all(iterable $promises): PromiseInterface
    {
        return Utils::all($promises);
    }

    /**
     * По завершению выполнения Promise-ов вернет
     * результат их выполнения
     *
     * Если количество выполненных Promise-ов меньше
     * переданного количества $count, то вернется
     * ошибка AggregateException
     *
     * @param int                        $count    Количество promise
     * @param iterable<PromiseInterface> $promises Promise-ы
     *
     * @return PromiseInterface
     */
    public function some(int $count, iterable $promises): PromiseInterface
    {
        return Utils::some($count, $promises);
    }

    /**
     * Тоже самое что и `some`, только в ответе будет не массив
     * значений, а само значение
     *
     * @param iterable<PromiseInterface> $promises Promise-ы
     *
     * @return PromiseInterface
     */
    public function any(iterable $promises): PromiseInterface
    {
        return Utils::any($promises);
    }

    /**
     * По завершению выполнения Promise-ов вернет результат
     * их выполнения вне зависимости от статуса
     *
     * В ответе будет массив результатов:
     * - state: (string) Статус выполнения (PromiseInterface::(FULFILLED|REJECTED))
     * - value|reason: (mixed) Либо результат выполнения, либо причина отклонения
     *
     * Если все переданные Promise были отклонены, то вернет
     * ошибку AggregateException, иначе, соответственно,
     * просто вернет результат выполнения всех Promise-ов
     *
     * @param iterable<PromiseInterface> $promises Promise-ы
     *
     * @return PromiseInterface
     */
    public function settle(iterable $promises): PromiseInterface
    {
        $results = [];

        return Each::of(
            $promises,
            static function ($value, $idx) use (&$results): void {
                $results[$idx] = [
                    'state' => PromiseInterface::FULFILLED,
                    'value' => $value
                ];
            },
            static function ($reason, $idx) use (&$results): void {
                $results[$idx] = [
                    'state' => PromiseInterface::REJECTED,
                    'reason' => $reason
                ];
            }
        )->then(
            static function () use (&$results) {
                $states = array_unique(
                    array_column($results, 'state'),
                    SORT_STRING
                );

                ksort($results);

                if (count($states) === 1
                    && $states[0] === PromiseInterface::REJECTED
                ) {
                    throw new AggregateException('All requests rejected', $results);
                }

                return $results;
            },
            static function ($reason = 'undefined') use (&$results) {
                $results[] = [
                    'state' => PromiseInterface::REJECTED,
                    'reason' => $reason
                ];

                ksort($results);
                return $results;
            }
        );
    }
}
