<?php
/**
 * Простая реализация PromiseResolverInterface
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

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Promise\Each;

/**
 * Простая реализация PromiseResolverInterface
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class PromiseResolver implements PromiseResolverInterface
{
    /**
     * Ожидает выполнения всех Promise и возвращает массив с
     * результатами их выполнения
     *
     * @param iterable<PromiseInterface> $promises Promises with worked method wait
     *
     * @return array
     * @throws Throwable on error
     */
    public function unwrap(iterable $promises): array
    {
        return Utils::unwrap($promises);
    }

    /**
     * Выполнить все переданные Promise и установить в возвращаемый
     * Promise результат их выполнения
     *
     * Если хоть 1 Promise был выполнен с ошибкой, то возвращаемый
     * тоже будет установлен в rejected, и вернет причину ошибки
     *
     * @param iterable<PromiseInterface> $promises Promises
     *
     * @return PromiseInterface
     */
    public function all(iterable $promises): PromiseInterface
    {
        return Utils::all($promises);
    }


    /**
     * Выполнить переданных Promise, и установить в возвращаемый
     * Promise результат выполнения, когда переданное количество
     * Promise было выполнено
     *
     * Promise установится в rejected и вернет ожибку AggregateException,
     * если количество выполненных Promise будет меньше $count
     *
     * @param int                        $count    Количество promise
     * @param iterable<PromiseInterface> $promises Promises
     *
     * @return PromiseInterface
     */
    public function some(int $count, iterable $promises): PromiseInterface
    {
        return Utils::some($count, $promises);
    }

    /**
     * Тоже самое что и some, только в ответе будет не массив
     * значений, а само значение
     *
     * @param iterable<PromiseInterface> $promises Promises
     *
     * @return PromiseInterface
     */
    public function any(iterable $promises): PromiseInterface
    {
        return Utils::any($promises);
    }

    /**
     * Возвращает Promise, который установится в fulfilled, когда
     * все переданные Promise выйдут из статуса pending.
     *
     * Если все переданные Promise были выполнены с ошибкой,
     * то возвращаемый Promise будет отклонен и передаст
     * все возникшие ошибки.
     *
     * Если хоть один переданный Promise был успешно завершен,
     * то возвращаемый Promise установится в fulfilled и
     * будет передан массив со всеми результатами выполнения
     * переданных Promise
     *
     * @param iterable<PromiseInterface> $promises Promises
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
                    array_column($results,'state'),
                    SORT_STRING
                );

                if (count($states) === 1
                    && $states[0] === PromiseInterface::REJECTED
                ) {
                    return new RejectedPromise($results);
                }

                return $results;
            },
            static function ($reason = 'undefined') use (&$results) {
                $results[] = [
                    'state' => PromiseInterface::REJECTED,
                    'reason' => $reason
                ];

                return $results;
            }
        );
    }
}
