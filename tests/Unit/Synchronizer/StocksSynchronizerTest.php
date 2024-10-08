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

namespace CashCarryShop\Sizya\Tests\Unit\Synchronizer;

use CashCarryShop\Sizya\Synchronizer\StocksSynchronizer;
use CashCarryShop\Sizya\Tests\Synchronizer\MockStocksSource;
use CashCarryShop\Sizya\Tests\Synchronizer\MockStocksTarget;
use CashCarryShop\Sizya\Tests\TestCase;
use CashCarryShop\Sizya\DTO\StockDTO;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирование класса StocksSynchronizer.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(StocksSynchronizer::class)]
class StocksSynchronizerTest extends TestCase
{
    public function testSynchronize(): void
    {
        $sourceWarehouses = \array_map(
            fn () => static::guidv4(),
            \array_fill(0, 5, null)
        );

        $targetWarehouses = \array_map(
            fn () => static::guidv4(),
            \array_fill(0, 5, null)
        );

        $articles = \array_map(
            fn () => static::fakeArticle(),
            \array_fill(0, 5, null)
        );

        $relations = [
            [
                'source' => [
                    $sourceWarehouses[0],
                    $sourceWarehouses[1]
                ],
                'target' => $targetWarehouses[0]
            ],
            [
                'source' => [
                    $sourceWarehouses[2]
                ],
                'target' => $targetWarehouses[1]
            ],
            [
                'source' => [
                    $sourceWarehouses[3]
                ],
                'target' => $targetWarehouses[2]
            ],
            [
                'source' => [
                    $sourceWarehouses[4]
                ],
                'target' => $targetWarehouses[3]
            ]
        ];

        $source = new MockStocksSource([
            'items' => [
                StockDTO::fromArray([
                    'id'          => static::guidv4(),
                    'article'     => $articles[0],
                    'warehouseId' => $sourceWarehouses[0],
                    'quantity'    => 1
                ]),
                StockDTO::fromArray([
                    'id'          => static::guidv4(),
                    'article'     => $articles[0],
                    'warehouseId' => $sourceWarehouses[1],
                    'quantity'    => 2
                ]),
                StockDTO::fromArray([
                    'id'          => static::guidv4(),
                    'article'     => $articles[1],
                    'warehouseId' => $sourceWarehouses[2],
                    'quantity'    => 4
                ]),
                StockDTO::fromArray([
                    'id'          => static::guidv4(),
                    'article'     => $articles[2],
                    'warehouseId' => $sourceWarehouses[3],
                    'quantity'    => 5
                ]),
                StockDTO::fromArray([
                    'id'          => static::guidv4(),
                    'article'     => $articles[3],
                    'warehouseId' => $sourceWarehouses[4],
                    'quantity'    => 6
                ])
            ]
        ]);

        $target = new MockStocksTarget([
            'items' => [
                StockDTO::fromArray([
                    'id'          => static::guidv4(),
                    'article'     => $articles[0],
                    'warehouseId' => $targetWarehouses[0],
                    'quantity'    => 0
                ]),
                StockDTO::fromArray([
                    'id'          => static::guidv4(),
                    'article'     => $articles[1],
                    'warehouseId' => $targetWarehouses[1],
                    'quantity'    => 12
                ]),
                StockDTO::fromArray([
                    'id'          => static::guidv4(),
                    'article'     => $articles[2],
                    'warehouseId' => $targetWarehouses[2],
                    'quantity'    => 81
                ]),
                StockDTO::fromArray([
                    'id'          => static::guidv4(),
                    'article'     => $articles[3],
                    'warehouseId' => $targetWarehouses[3],
                    'quantity'    => 99
                ])
            ]
        ]);

        $expected = [
            StockDTO::fromArray([
                'id'          => static::guidv4(),
                'article'     => $articles[0],
                'warehouseId' => $targetWarehouses[0],
                'quantity'    => 3
            ]),
            StockDTO::fromArray([
                'id'          => static::guidv4(),
                'article'     => $articles[1],
                'warehouseId' => $targetWarehouses[1],
                'quantity'    => 4
            ]),
            StockDTO::fromArray([
                'id'          => static::guidv4(),
                'article'     => $articles[2],
                'warehouseId' => $targetWarehouses[2],
                'quantity'    => 5
            ]),
            StockDTO::fromArray([
                'id'          => static::guidv4(),
                'article'     => $articles[3],
                'warehouseId' => $targetWarehouses[3],
                'quantity'    => 6
            ])
        ];

        $synchronizer = new StocksSynchronizer($source, $target);
        $synchronizer->synchronize([
            'throw'             => true,
            'default_warehouse' => $target->settings['warehouses'][4]['id'],
            'relations'         => $relations
        ]);

        \reset($target->settings['items']);
        foreach ($expected as $expectedItem) {
            $item = \current($target->settings['items']);

            $this->assertEquals(
                $expectedItem->article,
                $item->article,
                'Articles not equals'
            );

            $this->assertEquals(
                $expectedItem->warehouseId,
                $item->warehouseId,
                'Warehouse ids not equals'
            );

            $this->assertEquals(
                $expectedItem->quantity,
                $item->quantity,
                'Quantity not equals'
            );

            \next($target->settings['items']);
        }
    }
}
