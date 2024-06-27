<?php
/**
 * Элемент синхронизации
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

use CashCarryShop\Synchronizer\AbstractSynchronizer as DefaultAbstractSynchronizer;

/**
 * Элемент синхронизации
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class AbstractSynchronizer extends DefaultAbstractSynchronizer
{
    /**
     * Переменная, отражающая что синхронизатор выполняется
     *
     * @var bool
     */
    protected bool $running = false;

    /**
     * Проверить, выполняется ли в
     * текущий момент синхронизатор
     *
     * @return bool
     */
    final public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Синхронизировать
     *
     * @param array $settings Настройки для синхронизации
     *
     * @return bool
     */
    final public function synchronize(array $settings = []): bool
    {
        $this->running = true;
        try {
            return $this->process($settings);
        } catch (Throwable $exception) {
            $this->running = false;
            return false;
        }
    }

    /**
     * Запустить синхронизацию
     *
     * @param array $settings Настройки
     *
     * @return bool
     */
    abstract protected function process(array $settings): bool;
}
