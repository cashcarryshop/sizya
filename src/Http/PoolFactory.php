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

use GuzzleHttp\Promise\TaskQueueInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;

/**
 * Интерфейс Pool (бассейна) для запросов
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class PoolFactory implements PoolFactoryInterface
{
    /**
     * Используемый клиент
     *
     * @var ClientInterface
     */
    private ClientInterface $_client;

    /**
     * Используемая очередь
     *
     * @var TaskQueueInterface
     */
    private TaskQueueInterface $_queue;

    /**
     * Создать фабрику Pool
     *
     * @param ?ClientInterface    $client Клиент
     * @param ?TaskQueueInterface $queue  Очередь
     */
    public function __construct(
        ?ClientInterface $client = null,
        ?TaskQueueInterface $queue = null
    ) {
        $this->_client = $client ?? new Client;
        $this->_queue = $queue ?? Utils::queue();
    }

    /**
     * Создать Pool
     *
     * В конфиг для создания Pool должен обязательно принимать:
     *
     * В конфиг Pool должен обязательно принимать:
     *
     * - concurrency: (int) - Максимальное количество одновременно
     *   выполняемых запросов. Если значение установлено в 0 или
     *   не передано, ограничение не действует
     * - rate: (RateLimit) - Ограничение частоты выполнения запросов.
     *   Если значение установлено в 0 или не передано,
     *   ограничение не действует
     * - client: (ClientInterface) - Клиент для отправки запросов
     * - queue: (TaskQueueInterface) - Используемая очередь
     *
     * @param array $config Конфигурация для Pool
     *
     * @return PoolInterface
     */
    public function createPool(array $config = []): PoolInterface
    {
        return new Pool(
            array_merge(
                $config, [
                    'client' => $config['client'] ?? $this->_client,
                    'queue' => $config['queue'] ?? $this->_queue,
                ]
            )
        );
    }
}
