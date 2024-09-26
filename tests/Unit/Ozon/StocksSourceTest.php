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

        static::$handler->append(...static::makeProductsGetResponses($ids, $skus));

        $template = static::getResponseData(
            'api-seller.ozon.ru/v1/product/info/stocks-by-warehouse/fbs'
        )['body'];

        $templateRow = $template['result'][0];

        $template['result'] = \array_map(
            static fn ($id, $sku) => static::makeByWarehouseStock([
                'id'       => $id,
                'sku'      => $sku,
                'template' => $templateRow
            ]),
            $ids,
            $skus
        );

        static::$handler->append(static::createJsonResponse(body: $template));
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
