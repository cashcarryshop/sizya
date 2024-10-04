<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category TestMock
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Tests\Synchronizer;

use CashCarryShop\Sizya\DTO\StockDTO;
use CashCarryShop\Sizya\StocksGetterInterface;
use CashCarryShop\Synchronizer\SynchronizerSourceInterface;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithFakeData;

/**
 * Тестовый класс источника синхронизации остатков.
 *
 * @category TestMock
 * @package  Sizya
 * @author   TheWhatis <anton-gnogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class MockStocksSource implements SynchronizerSourceInterface, StocksGetterInterface
{
    use InteractsWithFakeData;

    /**
     * Настройки.
     *
     * @var array
     */
    protected array $settings;

    /**
     * Создать экземпляр источника.
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        $warehouses = $settings['warehouses'] ?? \array_map(
            fn () => ['id' => static::guidv4()],
            \array_fill(0, 5, null)
        );

        $products = $settings['products'] ?? \array_map(
            fn () => [
                'id'      => static::guidv4(),
                'article' => static::fakeArticle()
            ],
            \array_fill(0, 100, null)
        );

        $countWarehouses = \count($warehouses) - 1;
        $countProducts   = \count($products) - 1;

        $this->settings = \array_replace(
            [
                'warehouses' => $warehouses,
                'products'   => $products,
                'items'      => \array_map(
                    fn () => StockDTO::fromArray([
                        'id'          => ($product = $products[\random_int(0, $countProducts)])['id'],
                        'article'     => $product['article'],
                        'warehouseId' => $warehouses[\random_int(0, $countWarehouses)]['id'],
                        'quantity'    => \random_int(0, 10)
                    ]),
                    \array_fill(0, 1000, null)
                )
            ],
            $settings
        );
    }


    /**
     * Получить остатки товаров
     *
     * @see StockDTO
     *
     * @return StockDTO[]
     */
    public function getStocks(): array
    {
        return $this->settings['items'];
    }
}
