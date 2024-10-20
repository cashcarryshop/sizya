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
use CashCarryShop\Sizya\Ozon\Products;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithOzon;
use CashCarryShop\Sizya\Tests\Traits\ProductsGetterTests;
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
#[CoversClass(Products::class)]
class ProductsTest extends TestCase
{
    use InteractsWithOzon;
    use ProductsGetterTests;

    protected function setUpBeforeTestGetProducts(array $expected): void
    {
        $this->_prepareProducts($expected);

        static::$handler->append(
            static::createMethodResponse('v2/product/list', [
                'expected' => $expected
            ]),
            static::createMethodResponse('v2/product/info/list', [
                'expected' => $expected
            ])
        );
    }

    protected function getProductsByIdsProvider(): array
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
                $products = [
                    static::fakeProductDto([
                        'id'     => '555672251',
                        'prices' => [
                            static::fakePriceDto([
                                'id'   => 'price',
                                'name' => 'Price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'minPrice',
                                'name' => 'Min price'
                            ])
                        ]
                    ]),
                    static::fakeProductDto([
                        'id'     => '610161857',
                        'prices' => [
                            static::fakePriceDto([
                                'id'   => 'price',
                                'name' => 'Price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'minPrice',
                                'name' => 'Min price'
                            ])
                        ]
                    ]),
                    static::fakeProductDto([
                        'id'     => '651428131',
                        'prices' => [
                            static::fakePriceDto([
                                'id'   => 'price',
                                'name' => 'Price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'minPrice',
                                'name' => 'Min price'
                            ])
                        ]
                    ]),
                    static::fakeProductDto([
                        'id'     => '248262915',
                        'prices' => [
                            static::fakePriceDto([
                                'id'   => 'price',
                                'name' => 'Price'
                            ]),
                            static::fakePriceDto([
                                'id'   => 'minPrice',
                                'name' => 'Min price'
                            ])
                        ]
                    ]),
                    static::fakeProductDto([
                        'id'     => '110129554',
                        'prices' => [
                            static::fakePriceDto([
                                'id'   => 'price',
                                'name' => 'Price'
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
                'v2/product/info/list', [
                    'expected' => $products
                ]
            )
        );

        return $data;
    }

    protected function setUpBeforeTestGetProductsByArticles(
        array $expectedProducts,
        array $expectedErrors,
        array $expected
    ): void {
        $this->_prepareProducts($expectedProducts);

        static::$handler->append(
            static::createMethodResponse(
                'v2/product/info/list', [
                    'expected' => $expectedProducts
                ]
            )
        );
    }

    protected function createProductsGetter(): Products
    {
        return new Products([
            'token'    => 'token',
            'clientId' => 123321,
            'limit'    => 100,
            'client'   => static::createHttpClient(static::$handler)
        ]);
    }

    /**
     * Обработать объект ожидаемых товаров.
     *
     * @param array $products Товары
     *
     * @return void
     */
    private function _prepareProducts(array $products): void
    {
        foreach ($products as $product) {
            $product->id     = (string) \random_int(100000000, 999999999);
            $product->prices = \array_slice($product->prices, 1, 2);
            $product->type   = null;

            $product->prices[0]->id   = 'price';
            $product->prices[0]->name = 'Price';

            $product->prices[1]->id   = 'minPrice';
            $product->prices[1]->name = 'Min price';
        }
    }
}
