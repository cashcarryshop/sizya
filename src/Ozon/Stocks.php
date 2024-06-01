<?php
/**
 * Класс цели
 *
 * PHP version 8
 *
 * @category Ozon
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Ozon;

use Respect\Validation\Validator as v;
use CashCarryShop\Synchronizer\SynchronizerSourceInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Sizya\Synchronizer\InteractsWithDeferred;
use CashCarryShop\Promise\PromiseInterface;

/**
 * Класс с настройками и логикой обновления
 * остатков Ozon (target)
 *
 * PHP version 8
 *
 * @category Ozon
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class Stocks implements SynchronizerSourceInterface, SynchronizerTargetInterface
{
    use InteractsWithDeferred;

    /**
     * Настройки
     *
     * @var array
     */
    public readonly array $settings;

    /**
     * Создание экземпляра цели
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        $this->settings = [
            'clientId' => $settings['clientId'] ?? null,
            'token' => $settings['token'] ?? null
        ];

        v::keySet(
            v::key('clientId', v::intType()),
            v::key('token', v::stringType())
        )->assert($this->settings);

        $this->ozon($this->settings['clientId'], $this->settings['token'])
            ->extend('fbs', fn ($manager) => $this->composite([
                $manager->creator('v1/fbs'),
                $manager->creator('v2/fbs'),
                $manager->creator('v3/fbs'),
                $manager->creator('v4/fbs'),
                $manager->creator('v5/fbs'),
            ]));

        $this->ozon()->extend('stocks', fn ($manager) => $this->composite([
            $manager->creator('v1/stocks'),
            $manager->creator('v2/stocks'),
            $manager->creator('v3/stocks')
        ]));

        $this->ozon()->extend('prices', fn ($manager) => $this->composite([
            $manager->creator('v1/prices'),
            $manager->creator('v4/prices')
        ]));

        $this->ozon()->extend('products', fn ($manager) => $this->composite([
            $manager->creator('v1/products'),
            $manager->creator('v2/products'),
            $manager->creator('v3/products'),
            $manager->creator('v4/products'),
        ]));

        $this->ozon()->extend('rfbs', fn ($manager) => $manager->service('v2/rfbs'));
    }

    /**
     * Обновить остатки по складам
     *
     * @param array $stocks Остатки
     *
     * @return PromiseInterface
     */
    public function updateWarehouse(array $stocks): PromiseInterface
    {
        return $this->resolveThrow(function ($deferred) use ($stocks) {
            v::anyOf(
                v::each(v::allOf(
                    v::key('stock', v::intType()),
                    v::key('warehouse_id', v::intType()),
                    v::when(
                        v::key('offer_id', v::notEmpty()),
                        v::key('offer_id', v::stringType()),
                        v::key('product_id', v::intType())
                    ),
                )),
                v::equals([])
            )->assert($stocks);

            $response = $this->service('stocks')
                ->updateWarehouse(array_splice($stocks, 0, min(100, count($stocks))));

            if ($stocks) {
                return $this->updateWarehouse($stocks)->then(
                    fn ($nextResponse) => $deferred->resolve(
                        array_merge([$response], $nextResponse)
                    ),
                    fn ($exception) => $deferred->reject($exception)
                );
            }

            $deferred->resolve([$response]);
        });
    }

    /**
     * Обновить остатки
     *
     * @param array $stocks Остатки
     *
     * @return PromiseInterface
     */
    public function update(array $stocks): PromiseInterface
    {
        return $this->resolveThrow(function ($deferred) use ($stocks) {
            v::anyOf(
                v::each(v::allOf(
                    v::key('stock', v::intType()),
                    v::when(
                        v::key('offer_id', v::notEmpty()),
                        v::key('offer_id', v::stringType()),
                        v::key('product_id', v::intType())
                    ),
                )),
                v::equals([])
            )->assert($stocks);

            $response = $this->service('stocks')
                ->update(array_splice($stocks, 0, min(100, count($stocks))));

            if ($stocks) {
                return $this->update($stocks)->then(
                    fn ($nextResponse) => $deferred->resolve(
                        array_merge([$response], $nextResponse)
                    ),
                    fn ($exception) => $deferred->reject($exception)
                );
            }

            $deferred->resolve([$response]);
        });
    }
}
