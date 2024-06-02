<?php
/**
 * Фабрика для синхронизации
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Moysklad\Ozon;

use CashCarryShop\Sizya\Moysklad\Stocks as MoyskladStocks;
use CashCarryShop\Sizya\Ozon\Stocks as OzonStocks;

use CashCarryShop\Synchronizer\SynchronizerFactoryInterface;
use CashCarryShop\Synchronizer\SynchronizerSourceInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Synchronizer\SynchronizerInterface;
use DomainException;
use LogicException;

/**
 * Фабрика для синхронизации остатков МойСклад->Ozon
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
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
     * @return StocksSynchronizer
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
}
