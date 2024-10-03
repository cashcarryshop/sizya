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

use CashCarryShop\Sizya\Ozon\Products;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithOzon;
use CashCarryShop\Sizya\Tests\Traits\ProductsGetterTests;
use CashCarryShop\Sizya\Tests\TestCase;
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

    /**
     * Используемыая сущность.
     *
     * @var ?Products
     */
    protected static Products $entity;

    public static function setupBeforeClass(): void
    {
        static::$entity = new Products([
            'token'    => 'token',
            'clientId' => 123321,
            'limit'    => 100,
            'client'   => static::createHttpClient(static::$handler)
        ]);
    }

    protected function createProductsGetter(): ?Products
    {
        return static::$entity ?? null;
    }

    protected function setUpBeforeTestGetProducts(): void
    {
        static::$handler->append(
            static::createMethodResponse('v2/product/list', ['limit' => 100]),
            static::createMethodResponse('v2/product/info/list')
        );
    }

    protected function productsIdsProvider(): array
    {
        [
            'provides' => $ids,
            'invalid'  => $invalidIds
        ] = static::generateProvideData();

        static::$handler->append(
            ...\array_map(
                static fn () => static::createMethodResponse(
                    'v2/product/info/list', [
                        'notFound' => $invalidIds
                    ]
                ),
                $ids
            )
        );

        return $ids;
    }

    protected function productsArticlesProvider(): array
    {
        [
            'provides' => $articles,
            'invalid'  => $invalidArticles
        ] = static::generateProvideData([
            'validGenerator' => fn () => static::fakeArticle()
        ]);

        static::$handler->append(
            ...\array_map(
                 static fn () => static::createMethodResponse(
                    'v2/product/info/list', [
                        'notFound' => $invalidArticles
                    ]
                ),
                $articles
            )
        );

        return $articles;
    }

    public static function tearDownAfterClass(): void
    {
        static::$entity = null;
    }
}
