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

use CashCarryShop\Sizya\Ozon\StocksTarget;
use CashCarryShop\Sizya\DTO\StockUpdateDTO;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithOzon;
use CashCarryShop\Sizya\Tests\Traits\StocksUpdaterTests;
use CashCarryShop\Sizya\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для обновления остатков Ozon.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(StocksTarget::class)]
class StocksTargetTest extends TestCase
{
    use InteractsWithOzon;
    use StocksUpdaterTests;

    /**
     * Используемыая сущность.
     *
     * @var ?Products
     */
    protected static StocksTarget $entity;

    public static function setUpBeforeClass(): void
    {
        static::$entity = new StocksTarget([
            'token'    => 'token',
            'clientId' => 123321,
            'limit'    => 100,
            'client'   => static::createHttpClient(static::$handler)
        ]);
    }

    protected function createStocksUpdater(): ?StocksTarget
    {
        return static::$entity ?? null;
    }

    protected function updateStocksProvider(): array
    {
        [
            'provides' => $provides,
            'invalid'  => $invalid
        ] = static::generateProvideData([
            'validGenerator' => fn () => StockUpdateDTO::fromArray([
                'id'          => static::guidv4(),
                'article'     => static::fakeArticle(),
                'warehouseId' => static::guidv4(),
                'quantity'    => \random_int(0, 20)
            ]),
            'invalidGenerator' => fn () => \random_int(0, 4) === 3
                ? ['invalid']
                : StockUpdateDTO::fromArray([
                    'id'          => \random_int(0, 3) === 3 ?: static::guidv4(),
                    'article'     => static::fakeArticle(),
                    'warehouseId' => \random_int(-10000000, 10000000),
                    'quantity'    => \random_int(-10, 10)
                ])
        ]);

        static::$handler->append(
            ...\array_fill(
                0,
                \count($provides),
                static::createMethodResponse('v2/products/stocks', [
                    'captureBody' => function (&$body) use ($invalid) {
                        foreach ($body['result'] as $idx => $item) {
                            foreach ($invalid as $invalidItem) {
                                if ($item['product_id'] == $invalid->id) {
                                    $item['udpated'] = false;
                                    $item['errors'] = [
                                        'code'    => \random_int(0, 500),
                                        'message' => \random_int(0, 1) === 1
                                            ? 'Not Found item'
                                            : 'Internal error',
                                        'details' => [
                                            'typeUrl' => 'Some type url',
                                            'value'   => \random_int(0, 1)
                                                ? $invalid->id
                                                : $invalid->warehouseId
                                        ]
                                    ];
                                }
                            }
                        }
                    }
                ])
            )
        );

        return $provides;
    }

    public static function tearDownAfterClass(): void
    {
        static::$entity = null;
    }
}
