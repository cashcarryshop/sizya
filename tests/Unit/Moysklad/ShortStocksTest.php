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

namespace CashCarryShop\Sizya\Tests\Unit\Moysklad;

use CashCarryShop\Sizya\Moysklad\ShortStocks;
use CashCarryShop\Sizya\Tests\Traits\StocksGetterTests;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithMoysklad;
use CashCarryShop\Sizya\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для получения остатков МойСклад.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(ShortStocks::class)]
class ShortStocksTest extends TestCase
{
    use InteractsWithMoysklad;
    use StocksGetterTests;

    /**
     * Сущность.
     *
     * @var ?CustomerOrdersSource
     */
    protected static ?ShortStocks $entity = null;


    public static function setUpBeforeClass(): void
    {
        static::$entity = new ShortStocks([
            'credentials' => ['login', 'password'],
            'client'      => static::createHttpClient(static::$handler),
            'stockType'   => 'quantity'
        ]);
    }

    protected function createStocksGetter(): ?ShortStocks
    {
        return static::$entity;
    }

    protected function setUpBeforeTestGetStocks(): void
    {
        $ids = \array_map(
            fn () => static::guidv4(),
            \array_fill(0, 100, null)
        );

        $storeId = static::guidv4();

        $template = static::getResponseData(
            'api.moysklad.ru/api/remap/1.2/report/stock/bystore/current'
        )['body'][0];

        static::$handler->append(
            static::createJsonResponse(
                body: \array_map(
                    fn ($id) => static::createShortStock([
                        'id'       => $id,
                        'storeId'  => \random_int(0, 30) === 30 ? static::guidv4() : $storeId,
                        'template' => $template
                    ]),
                    $ids
                )
            )
        );

        static::$handler->append(...static::makeProductsGetByIdsResponses([$ids], []));
    }

    public static function tearDownAfterClass(): void
    {
        static::$entity = null;
    }
}
