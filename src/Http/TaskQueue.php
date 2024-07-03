<?php
declare(strict_types=1);

namespace CashCarryShop\Sizya\Http;

use GuzzleHttp\Promise\TaskQueueInterface;
use Generator;

/**
 * Собственная очередь задач FIFO, за счёт которой имеется возможность
 * не блоировать запуск обработчиков, используя ограничение rate limit
 * внутри Pool
 *
 * По-сути, во время запуска run, перебирает все генераторы, внутри
 * которых производиться ожидание разрешения для отправки
 * нового запроса, следуя rate limit (см. Pool)
 *
 * В конце работы скрипта (на shutdown), перед уничтожением
 * объекта, задачи вызываются также как и в стандартном TaskQueue
 * из Guzzle.
 */
class TaskQueue implements TaskQueueInterface
{
    private $enableShutdown = true;
    private $queue = [];

    public function __construct(bool $withShutdown = true)
    {
        if ($withShutdown) {
            register_shutdown_function(function (): void {
                if ($this->enableShutdown) {
                    // Only run the tasks if an E_ERROR didn't occur.
                    $err = error_get_last();
                    if (!$err || ($err['type'] ^ E_ERROR)) {
                        $this->run(true);
                    }
                }
            });
        }
    }

    public function isEmpty(): bool
    {
        return !$this->queue;
    }

    public function add(callable|Generator $task): void
    {
        $this->queue[] = $task;
    }

    public function run(bool $shutdown = false): void
    {
        $used = [];

        while ($task = array_shift($this->queue)) {
            if ($task instanceof Generator) {
                if (!$shutdown) {
                    if (in_array($task, $used)) {
                        $this->queue[] = $task;
                        break;
                    }

                    $used[] = $task;
                }

                if ($task->valid()) {
                    $task->next();
                    $this->queue[] = $task;
                }

                continue;
            }

            $task();
        }
    }

    /**
     * The task queue will be run and exhausted by default when the process
     * exits IFF the exit is not the result of a PHP E_ERROR error.
     *
     * You can disable running the automatic shutdown of the queue by calling
     * this function. If you disable the task queue shutdown process, then you
     * MUST either run the task queue (as a result of running your event loop
     * or manually using the run() method) or wait on each outstanding promise.
     *
     * Note: This shutdown will occur before any destructors are triggered.
     */
    public function disableShutdown(): void
    {
        $this->enableShutdown = false;
    }
}
