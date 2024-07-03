<?php
/**
 * Основной Pool
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
use GuzzleHttp\Promise\TaskQueueInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;

/**
 * Основной Pool
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class Pool implements PoolInterface
{
    /**
     * Используемый клиент
     *
     * @var ClientInterface
     */
    private ClientInterface $_client;

    /**
     * Ограничение частоты
     *
     * @var ?RateLimit
     */
    private ?RateLimit $_rate = null;

    /**
     * Ограничение на количество параллельных запросов
     *
     * @var int
     */
    private int $_concurrency;

    /**
     * Очередь
     *
     * @var TaskQueueInterface
     */
    private TaskQueueInterface $_queue;

    /**
     * Promise ожидающие результат выполненияx
     *
     * @var array<PromiseInterface>
     */
    private array $_pending = [];

    /**
     * Запросы ожидающие выполнения
     *
     * @var array<array<string, RequestInterface|PromiseInterface>>
     */
    private array $_requests = [];

    /**
     * Счетчик для ограничителя частоты
     *
     * @var int
     */
    private int $_counter = 0;

    /**
     * Таймер для ограничителя частоты
     *
     * @var float
     */
    private float $_timer;

    /**
     * Создать экземпляр Pool
     *
     * Конфиг:
     *
     * - concurrency: (int) - Максимальное количество одновременно
     *   выполняемых запросов. Если значение установлено в 0 или
     *   не передано, ограничение не действует
     * - rate: (RateLimit) - Ограничение частоты выполнения запросов.
     *   Если значение установлено в 0 или не передано,
     *   ограничение не действует
     * - client: (ClientInterface) - Клиент для отправки запросов
     * - queue: (TaskQueueInterface) - Очередь, где будут находиться задачи
     *   ожидающие разрешения по Rate Limit
     *
     * @param array $config Конфигурация
     */
    public function __construct(array $config = [])
    {
        $this->_client = $config['client'] ?? new Client;
        $this->_queue = $config['queue'] ?? Utils::queue();

        if (isset($config['rate'])) {
            $this->_rate = $config['rate'];

            if (!$this->_rate->getSeconds() || !$this->_rate->getAmount()) {
                $this->_rate = null;
            }
        }

        $this->_timer = microtime(true);
        $this->_concurrency = $config['concurrency'] ?? 0;
    }

    /**
     * Проверить есть ли доступные worker-ы
     *
     * @return bool
     */
    private function _hasWorkers(): bool
    {
        if ($this->_concurrency === 0) {
            return true;
        }

        if (max($this->_concurrency - count($this->_pending), 0)) {
            return true;
        }

        return false;
    }

    /**
     * Проверить что есть квота на частоту выполнения
     *
     * @return bool
     */
    private function _hasRateQuota(): bool
    {
        if ($this->_rate) {
            $now = microtime(true);
            $delta = $now - $this->_timer;
            if ($delta > $this->_rate->getSeconds()) {
                $this->_timer = $now;
                $this->_counter = 0;
            }

            if ($this->_counter >= $this->_rate->getAmount()) {
                return false;
            }

            ++$this->_counter;
        }

        return true;
    }

    /**
     * Отправить запрос
     *
     * @param RequestInterface $request Запрос
     *
     * @return PromiseInterface
     */
    private function _send(RequestInterface $request): PromiseInterface
    {
        $promise = $this->_client->sendAsync($request);

        $finally = function () use ($promise, $request) {
            if ($index = array_search($promise, $this->_pending, true)) {
                unset($this->_pending[$index]);
            }

            if ($pending = array_shift($this->_requests)) {
                $generator = (
                    function ($pending) {
                        while (!$this->_hasRateQuota()) {
                            yield usleep(250);
                        }

                        $pending['promise']->resolve(
                            $this->_send($pending['request'])
                        );
                    }
                )($pending);

                if ($this->_queue instanceof TaskQueue) {
                    return $this->_queue->add($generator);
                }

                while ($generator->valid()) {
                    $generator->next();
                }
            }
        };

        $promise->then($finally, $finally);
        return $this->_pending[] = $promise;
    }

    /**
     * Отправить запрос (добавить в Pool)
     *
     * @param RequestInterface $request Запрос
     *
     * @return PromiseInterface
     */
    public function add(RequestInterface $request): PromiseInterface
    {
        if ($this->_hasWorkers() && $this->_hasRateQuota()) {
            return $this->_send($request);
        }

        $this->_requests[] = [
            'request' => $request,
            'promise' => $promise = new Promise(
                function () use (&$promise) {
                    while (
                        $promise->getState() === PromiseInterface::PENDING
                            && $pending = array_shift($this->_pending)
                    ) {
                        $pending->wait();
                    }
                }
            )
        ];

        return $promise;
    }
}
