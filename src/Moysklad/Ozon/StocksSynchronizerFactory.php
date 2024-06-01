<?php
/**
 * Фабрика для синхронизации
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Moysklad\Ozon;

use CashCarryShop\Synchronizer\SynchronizerFactoryInterface;
use CashCarryShop\Synchronizer\SynchronizerSourceInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Synchronizer\SynchronizerInterface;
use DomainException;
use LogicException;

/**
 * Фабрика для синхронизации остатков
 *
 * @category Moysklad
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class StocksSynchronizerFactory implements SynchronizerFactoryInterface
{
    /**
     * Создать синхронизацию
     *
     * @param array $sourceSettings Настройки источника
     * @param array $targetSettings Настройки цели
     *
     * @return SynchronizerInterface
     */
    public function create(
        array $sourceSettings,
        array $targetSettings
    ): StocksSynchronizer {
        return new StocksSynchronizer(
            $this->createSource($sourceSettings),
            $this->createTarget($targetSettings),
        );
    }

    /**
     * Создать источник синхронизации
     *
     * @param array $settings Настройки
     *
     * @return MoyskladStocks
     */
    public function createSource(array $settings): MoyskladStocks
    {
        return new MoyskladStocks($settings);
    }

    /**
     * Создать цель синхронизации
     *
     * @param array $settings Настройки
     *
     * @return OzonStocks
     */
    public function createTarget(array $settings): OzonStocks
    {
        return new OzonStocks($settings);
    }

    /**
     * Зарегестрировать источник
     *
     * @param string $type  Тип
     * @param string $class Класс
     *
     * @return void
     * @throws DomainException
     * @throws LogicException
     */
    public function registerSource($type, $class): void
    {
        // ...
    }

    /**
     * Зарегестрировать цель
     *
     * @param string $type  Тип
     * @param string $class Класс
     *
     * @return void
     * @throws DomainException
     * @throws LogicException
     */
    public function registerTarget($type, $class): void
    {
        // ...
    }
}
