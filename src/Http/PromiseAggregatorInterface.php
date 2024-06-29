<?php
/**
 * Класс с набором методов, позволяющие
 * объеденить несколько Promise-ов
 * в один
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
use GuzzleHttp\Promise\AggregateException;
use Throwable;

/**
 * Класс с набором методов, позволяющие
 * объеденить несколько Promise-ов
 * в один
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface PromiseAggregatorInterface
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
    public function unwrap(iterable $promises): array;

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
    public function all(iterable $promises): PromiseInterface;

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
    public function some(int $count, iterable $promises): PromiseInterface;

    /**
     * Тоже самое что и `some`, только в ответе будет не массив
     * значений, а само значение
     *
     * @param iterable<PromiseInterface> $promises Promise-ы
     *
     * @return PromiseInterface
     */
    public function any(iterable $promises): PromiseInterface;

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
    public function settle(iterable $promises): PromiseInterface;
}
