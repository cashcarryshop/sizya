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

use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\Ozon\ProductsPricesSource;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithOzon;
use CashCarryShop\Sizya\Tests\Traits\ProductsPricesGetterTests;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для получения товаров Ozon.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(ProductsPricesSource::class)]
class ProductsPricesSourceTest extends TestCase
{
    use InteractsWithOzon;
    use ProductsPricesGetterTests;

    protected function setUpBeforeTestGetProductsPrices(array $expected): void
    {
        $this->_prepareProducts($expected);

        static::$handler->append(
            static::createMethodResponse(
                'v4/product/info/prices', [
                    'expected' => $expected
                ]
            )
        );
    }

    protected function getProductsPricesByIdsProvider(): array
    {
        $data = [
            [
                // Valid ids
                '555672251',
                '610161857',
                '651428131',
                '248262915',
                '110129554',

                // Invalid ids
                '668957496',
                '668957496',
                '833076380',
                '804216824',
                '227465657',
                '110129554',
            ],
            \array_merge(
                $productsPrices = [
                    static::fakeProductPricesDto([
                        'id'     => '555672251',
                        'prices' => [
                            static::fakePriceDto([
                                'id'   => 'price',
                                'name' => 'Price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'oldPrice',
                                'name' => 'Old price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'minPrice',
                                'name' => 'Min price'
                            ])
                        ]
                    ]),
                    static::fakeProductPricesDto([
                        'id'     => '610161857',
                        'prices' => [
                            static::fakePriceDto([
                                'id'   => 'price',
                                'name' => 'Price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'oldPrice',
                                'name' => 'Old price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'minPrice',
                                'name' => 'Min price'
                            ])
                        ]
                    ]),
                    static::fakeProductPricesDto([
                        'id'     => '651428131',
                        'prices' => [
                            static::fakePriceDto([
                                'id'   => 'price',
                                'name' => 'Price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'oldPrice',
                                'name' => 'Old price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'minPrice',
                                'name' => 'Min price'
                            ])
                        ]
                    ]),
                    static::fakeProductPricesDto([
                        'id'     => '248262915',
                        'prices' => [
                            static::fakePriceDto([
                                'id'   => 'price',
                                'name' => 'Price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'oldPrice',
                                'name' => 'Old price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'minPrice',
                                'name' => 'Min price'
                            ])
                        ]
                    ]),
                    static::fakeProductPricesDto([
                        'id'     => '110129554',
                        'prices' => [
                            static::fakePriceDto([
                                'id'   => 'price',
                                'name' => 'Price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'oldPrice',
                                'name' => 'Old price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'minPrice',
                                'name' => 'Min price'
                            ])
                        ]
                    ])
                ],
                [
                    ByErrorDTO::fromArray([
                        'type' => ByErrorDTO::NOT_FOUND,
                        'value' => '668957496'
                    ]),
                    ByErrorDTO::fromArray([
                        'type' => ByErrorDTO::DUPLICATE,
                        'value' => '668957496'
                    ]),
                    ByErrorDTO::fromArray([
                        'type' => ByErrorDTO::NOT_FOUND,
                        'value' => '833076380'
                    ]),
                    ByErrorDTO::fromArray([
                        'type' => ByErrorDTO::NOT_FOUND,
                        'value' => '804216824'
                    ]),
                    ByErrorDTO::fromArray([
                        'type' => ByErrorDTO::NOT_FOUND,
                        'value' => '227465657'
                    ]),
                    ByErrorDTO::fromArray([
                        'type' => ByErrorDTO::DUPLICATE,
                        'value' => '110129554'
                    ])
                ]
            )
        ];

        static::$handler->append(
            static::createMethodResponse(
                'v4/product/info/prices', [
                    'expected' => $productsPrices
                ]
            )
        );

        return $data;
    }

    protected function setUpBeforeTestGetProductsPricesByArticles(
        array $expectedProductsPrices,
        array $expectedErrors,
        array $expected
    ): void {
        $this->_prepareProducts($expectedProductsPrices);

        static::$handler->append(
            static::createMethodResponse(
                'v4/product/info/prices', [
                    'expected' => $expectedProductsPrices
                ]
            )
        );
    }

    protected function createProductsPricesGetter(): ProductsPricesSource
    {
        return new ProductsPricesSource([
            'token'    => 'token',
            'clientId' => 123321,
            'limit'    => 100,
            'client'   => static::createHttpClient(static::$handler)
        ]);
    }

    /**
     * Обработать объект ожидаемых цен товаров.
     *
     * @param array $productsPrices Цены товаров
     *
     * @return void
     */
    private function _prepareProducts(array $productsPrices): void
    {
        foreach ($productsPrices as $productPrices) {
            $productPrices->id     = (string) \random_int(100000000, 999999999);
            $productPrices->prices = \array_slice($productPrices->prices, 0, 3);

            $productPrices->prices[0]->id   = 'price';
            $productPrices->prices[0]->name = 'Price';

            $productPrices->prices[1]->id   = 'oldPrice';
            $productPrices->prices[1]->name = 'Old price';

            $productPrices->prices[2]->id   = 'minPrice';
            $productPrices->prices[2]->name = 'Min price';
        }
    }
}
