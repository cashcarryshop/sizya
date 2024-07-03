<?php
/**
 * Элемент синхронизации, взаимодействующий с протоколом Http
 *
 * PHP version 8
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Synchronizer;

use CashCarryShop\Sizya\Http\PromiseAggregatorInterface;
use CashCarryShop\Sizya\Http\PromiseAggregator;
use CashCarryShop\Sizya\Http\PoolFactoryInterface;
use CashCarryShop\Sizya\Http\PoolFactory;
use CashCarryShop\Sizya\Http\PoolInterface;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;

/**
 * Элемент синхронизации, взаимодействующий с протоколом Http
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class HttpSynchronizerDualRole extends AbstractSynchronizerDualRole
{
    /**
     * Фабрика Pool-ов
     *
     * @var PoolFactoryInterface
     */
    protected PoolFactoryInterface $poolFactory;

    /**
     * Promise Aggregator
     *
     * @var PromiseAggregatorInterface
     */
    protected PromiseAggregatorInterface $promiseAggregator;

    /**
     * Установить отправителя запросов
     *
     * @param PoolFactoryInterface $factory Отправитель
     *
     * @return static
     */
    final public function withPoolFactory(PoolFactoryInterface $factory): static
    {
        $this->poolFactory = $factory;
        return $this;
    }

    /**
     * Получить отправитель запросов
     *
     * @return PoolFactoryInterface
     */
    final public function getPoolFactory(): PoolFactoryInterface
    {
        return $this->poolFactory ??= new PoolFactory;
    }

    /**
     * Установить Promise Aggregator
     *
     * @param PromiseAggregatorInterface $aggregator Promise Aggregator
     *
     * @return static
     */
    final public function withPromiseAggregator(
        PromiseAggregatorInterface $aggregator
    ): static {
        $this->promiseAggregator = $aggregator;
        return $this;
    }

    /**
     * Получить Promise Aggregator
     *
     * @return PromiseAggregatorInterface
     */
    final public function getPromiseAggregator(): PromiseAggregatorInterface
    {
        return $this->promiseAggregator ??= new PromiseAggregator;
    }

    /**
     * Создать PoolInterface
     *
     * См. PoolFactoryInterface::createPool
     *
     * @param array $config Конфиг для PoolFactoryInterface::createPool
     *
     * @return PoolInterface
     */
    public function createPool(array $config = []): PoolInterface
    {
        return $this->getPoolFactory()->createPool($config);
    }
}
