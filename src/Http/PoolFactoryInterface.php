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

/**
 * Интерфейс Pool (бассейна) для запросов
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface PoolFactoryInterface
{
    /**
     * Создать Pool
     *
     * В конфиг для создания Pool должен обязательно принимать:
     *
     * - concurrency: (int) - Максимальное количество одновременно
     *   выполняемых запросов. Если значение установлено в 0 или
     *   не передано, ограничение не действует
     * - rate: (RateLimit) - Ограничение частоты выполнения запросов.
     *   Если значение установлено в 0 или не передано,
     *   ограничение не действует
     *
     * @param array $config Конфигурация для Pool
     *
     * @return PoolInterface
     */
    public function createPool(array $config = []): PoolInterface;
}
