<?php
/**
 * Интерфейс Pool (бассейна) для запросов
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

use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Интерфейс Pool для запросов
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface PoolInterface
{
    /**
     * Создать экземпляр Pool
     *
     * В конфиг Pool должен обязательно принимать:
     *
     * - concurrency: (int) - Максимальное количество одновременно
     *   выполняемых запросов. Если значение установлено в 0 или
     *   не передано, ограничение не действует
     * - rate: (RateLimiter) - Ограничитель частоты выполнения запросов.
     *   Если значение счетчика или таймера установлено в 0 или не передано,
     *   ограничение не действует
     *
     * @param array $config Конфигурация
     */
    public function __construct(array $config = []);

    /**
     * Добавить запрос
     *
     * @param RequestInterface $request Запрос
     *
     * @return PromiseInterface
     */
    public function add(RequestInterface $request): PromiseInterface;
}
