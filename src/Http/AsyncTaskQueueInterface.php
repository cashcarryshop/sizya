<?php
declare(strict_types=1);

namespace CashCarryShop\Sizya\Http;

use GuzzleHttp\Promise\TaskQueueInterface;
use Generator;

/**
 * Интерфейс очереди с возможностью выполнять задачи асинхронно
 */
interface AsyncTaskQueueInterface extends TaskQueueInterface
{
    public function add(callable|Generator $task): void;
}
