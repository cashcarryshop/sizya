<?php
/**
 * Интерфейс, который предоставляет возможность
 * производить популярные способы взаимодействия
 * с множественным количеством Promise
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
use Throwable;

/**
 * Интерфейс, который предоставляет возможность
 * производить популярные способы взаимодействия
 * с множественным количеством Promise
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface PromiseResolverInterface
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
    public function unwrap(iterable $promises): array;

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
    public function all(iterable $promises): PromiseInterface;

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
    public function some(int $count, iterable $promises): PromiseInterface;

    /**
     * Тоже самое что и some, только в ответе будет не массив
     * значений, а само значение
     *
     * @param iterable<PromiseInterface> $promises Promises
     *
     * @return PromiseInterface
     */
    public function any(iterable $promises): PromiseInterface;

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
    public function settle(iterable $promises): PromiseInterface;
}
