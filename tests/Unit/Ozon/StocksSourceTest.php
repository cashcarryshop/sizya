<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Tests\Unit\Ozon;

use CashCarryShop\Sizya\Ozon\StocksSource;
use CashCarryShop\Sizya\Tests\Traits\StocksGetterTests;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithOzon;
use CashCarryShop\Sizya\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для получения остатков Ozon.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(StocksSource::class)]
class StocksSourceTest extends TestCase
{
    use InteractsWithOzon;
    use StocksGetterTests;

    /**
     * Сущность.
     *
     * @var ?CustomerOrdersSource
     */
    protected static ?StocksSource $entity = null;

    public static function setUpBeforeClass(): void
    {
        static::$entity = new StocksSource([
            'token'    => 'token',
            'clientId' => 123321,
            'limit'    => 100,
            'client'   => static::createHttpClient(static::$handler)
        ]);
    }

    protected function setUpBeforeTestGetStocks(): void
    {
        $ids = \array_map(
            fn () => \random_int(100000000, 999999999),
            \array_fill(0, 100, null)
        );

        $skus = \array_map(
            static fn () => \random_int(100000000, 999999999),
            $ids
        );

        static::$handler->append(
            static::createMethodResponse('v2/product/list', [
                'items' => \array_map(
                    fn ($id) => [
                        'id'      => $id,
                        'article' => static::fakeArticle()
                    ],
                    $ids
                )

            ]),
            static::createMethodResponse('v2/product/info/list', [
                'captureItems' => function (&$items) use ($ids, $skus) {
                    $itemsIds = \array_column($items, 'id');

                    \asort($itemsIds, SORT_NUMERIC);
                    \asort($ids,      SORT_NUMERIC);

                    \reset($ids);
                    foreach (\array_keys($itemsIds) as $idx) {
                        $items[$idx]['sku'] = $skus[\key($ids)];
                        \next($ids);
                    }
                }
            ]),
            static::createMethodResponse('v1/product/info/stocks-by-warehouse/fbs')
        );
    }

    protected function createStocksGetter(): ?StocksSource
    {
        return static::$entity;
    }

    public static function tearDownAfterClass(): void
    {
        static::$entity = null;
    }
}
