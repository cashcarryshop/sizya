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
 * Интерфейс Pool (бассейна) для запросов
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
final class Ratelimit
{
    /**
     * Количество запросов
     *
     * @var int
     */
    private int $_amount;

    /**
     * Время ограничения в секундах
     *
     * @var int
     */
    private int $_seconds;

    /**
     * Создать ограничитель
     *
     * @param int $amount  Количество запросов
     * @param int $seconds Во сколько секунд
     */
    public function __construct(int $amount, int $seconds)
    {
        $this->_amount = $amount;
        $this->_seconds = $seconds;
    }

    /**
     * Получить ограничение в секунду
     *
     * @param int $amount Количество запросов
     *
     * @return static
     */
    public static function perSecond(int $amount): static
    {
        return new static($amount, 1);
    }

    /**
     * Получить ограничение в минуту
     *
     * @param int $amount Количество запросов
     *
     * @return static
     */
    public static function perMinute(int $amount): static
    {
        return new static($amount, 60);
    }

    /**
     * Получить ограничение в минутах
     *
     * @param int $amount  Количество запросов
     * @param int $minutes Сколько минут
     *
     * @return static
     */
    public static function perMinutes(int $amount, int $minutes): static
    {
        return new static($amount, $minutes * 60);
    }

    /**
     * Получить количество доступных запросов
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->_amount;
    }

    /**
     * Получить секунды
     *
     * @return int
     */
    public function getSeconds(): int
    {
        return $this->_seconds;
    }
}
