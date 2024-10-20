<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Tests\Traits;

use CashCarryShop\Sizya\ProductsPricesGetterInterface;
use CashCarryShop\Sizya\DTO\ByErrorDTO;

/**
 * Трейт с тестами получения товаров.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see ProductsPricesGetterInterface
 */
trait ProductsPricesGetterTests
{
    use InteractsWithFakeData;
    use ProductsPricesAssertions;

    public function testGetProductsPrices(): void
    {
        $getter = $this->createProductsPricesGetter();

        $expected = \array_map(
            fn () => static::fakeProductPricesDto(),
            \array_fill(0, 10, null)
        );

        $this->setUpBeforeTestGetProductsPrices($expected);

        $this->assertProductsPrices($expected, $getter->getProductsPrices());
    }

    public function testGetProductsPricesByIds(): void
    {
        [$ids, $expected] = $this->getProductsPricesByIdsProvider();

        $getter = $this->createProductsPricesGetter();

        $this->assertProductsPrices(
            $expected,
            $getter->getProductsPricesByIds($ids)
        );
    }

    public function testGetProductsPricesByArticles(): void
    {
        $expected = \array_merge(
            $expectedProductsPrices = [
                static::fakeProductPricesDto(['article' => 'CCS00555']),
                static::fakeProductPricesDto(['article' => 'CCS00289']),
                static::fakeProductPricesDto(['article' => 'CCS00473']),
                static::fakeProductPricesDto(['article' => 'CCS00558']),
                static::fakeProductPricesDto(['article' => 'CCS00409']),
            ],
            $expectedErrors = [
                ByErrorDTO::fromArray([
                    'type'  => ByErrorDTO::NOT_FOUND,
                    'value' => 'CCS00301'
                ]),
                ByErrorDTO::fromArray([
                    'type'  => ByErrorDTO::DUPLICATE,
                    'value' => 'CCS00301'
                ]),
                ByErrorDTO::fromArray([
                    'type'  => ByErrorDTO::NOT_FOUND,
                    'value' => 'CCS00347'
                ]),
                ByErrorDTO::fromArray([
                    'type'  => ByErrorDTO::NOT_FOUND,
                    'value' => 'CCS00795'
                ]),
                ByErrorDTO::fromArray([
                    'type'  => ByErrorDTO::NOT_FOUND,
                    'value' => 'CCS00219'
                ]),
                ByErrorDTO::fromArray([
                    'type'  => ByErrorDTO::DUPLICATE,
                    'value' => 'CCS00409'
                ])
            ]
        );

        $getter = $this->createProductsPricesGetter();

        $this->setUpBeforeTestGetProductsPricesByArticles(
            $expectedProductsPrices,
            $expectedErrors,
            $expected
        );

        $this->assertProductsPrices(
            $expected, $getter->getProductsPricesByArticles(
                \array_merge(
                    \array_column($expectedProductsPrices, 'article'),
                    \array_column($expectedErrors, 'value')
                )
            )
        );
    }

    abstract protected function createProductsPricesGetter(): ProductsPricesGetterInterface;

    protected function setUpBeforeTestGetProductsPrices(array $expected): void
    {
        // ...
    }

    protected function getProductsPricesByIdsProvider(): array
    {
        return [
            [
                // Valid ids
                'aeeaa834-d679-4db4-9d8e-0e2233be5651',
                '4446c69b-d46b-49bb-87f1-0b626dcbff73',
                'b8e63a83-d73b-4794-b579-51c85ec5f057',
                'af7343aa-df5d-405e-9a68-a4baa5288e1a',
                '45561b18-5f48-46ac-80db-8974e69984e6',

                // Invalid ids
                '690ac8f6-4731-4795-89ec-29c4af4a22b6',
                '690ac8f6-4731-4795-89ec-29c4af4a22b6',
                '618cae53-9781-4c27-a7d6-4012818696a5',
                '9cf96d21-f3df-4de7-b210-cf96b29aa420',
                '786810cf-d841-49b0-90e9-96282624e9b4',
                '45561b18-5f48-46ac-80db-8974e69984e6',
            ],
            [
                static::fakeProductPricesDto([
                    'id' => 'aeeaa834-d679-4db4-9d8e-0e2233be5651'
                ]),
                static::fakeProductPricesDto([
                    'id' => '4446c69b-d46b-49bb-87f1-0b626dcbff73'
                ]),
                static::fakeProductPricesDto([
                    'id' => 'b8e63a83-d73b-4794-b579-51c85ec5f057'
                ]),
                static::fakeProductPricesDto([
                    'id' => 'af7343aa-df5d-405e-9a68-a4baa5288e1a'
                ]),
                static::fakeProductPricesDto([
                    'id' => '45561b18-5f48-46ac-80db-8974e69984e6'
                ]),
                ByErrorDTO::fromArray([
                    'type' => ByErrorDTO::NOT_FOUND,
                    'value' => '690ac8f6-4731-4795-89ec-29c4af4a22b6'
                ]),
                ByErrorDTO::fromArray([
                    'type' => ByErrorDTO::DUPLICATE,
                    'value' => '690ac8f6-4731-4795-89ec-29c4af4a22b6'
                ]),
                ByErrorDTO::fromArray([
                    'type' => ByErrorDTO::NOT_FOUND,
                    'value' => '618cae53-9781-4c27-a7d6-4012818696a5'
                ]),
                ByErrorDTO::fromArray([
                    'type' => ByErrorDTO::NOT_FOUND,
                    'value' => '9cf96d21-f3df-4de7-b210-cf96b29aa420'
                ]),
                ByErrorDTO::fromArray([
                    'type' => ByErrorDTO::NOT_FOUND,
                    'value' => '786810cf-d841-49b0-90e9-96282624e9b4'
                ]),
                ByErrorDTO::fromArray([
                    'type' => ByErrorDTO::DUPLICATE,
                    'value' => '45561b18-5f48-46ac-80db-8974e69984e6'
                ])
            ]
        ];
    }

    protected function setUpBeforeTestGetProductsPricesByArticles(
        array $expectedProductsPrices,
        array $expectedErrors,
        array $expected
    ): void {
        // ...
    }
}
