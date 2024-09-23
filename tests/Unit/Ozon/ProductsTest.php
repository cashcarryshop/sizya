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

namespace Tests\Unit\Ozon;

use CashCarryShop\Sizya\Ozon\Products;
use Tests\Traits\InteractsWithOzon;
use Tests\Traits\ProductsGetterTests;
use Tests\Traits\GetFromDatasetTrait;
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
    use GetFromDatasetTrait;
    use ProductsGetterTests;

    /**
     * Используемыая сущность.
     *
     * @var ?Orders
     */
    protected static ?Products $entity = null;

    protected static function setUpBeforeClassByOzon(array $credentials): void
    {
        if (is_null(static::getFromDataset(Products::class))) {
            static::markTestSkipped('Dataset for Ozon products not found');
        }

        static::$entity = new Products($credentials);

        // Проверка что данные авторизации верные
        // и что есть права на писпользование
        // метода api.
        static::$entity->getProducts();
    }

    protected function createProductsGetter(): ?Products
    {
        return static::$entity;
    }

    public static function productsIdsProvider(): array
    {
        return static::generateIds(
            static::getFromDataset(Products::class),
            \array_map(
                static fn () => 'invalidId',
                array_fill(0, 10, null)
            )
        );
    }

    public static function productsArticlesProvider(): array
    {
        return static::generateArticles(
            static::getFromDataset(Products::class),
            \array_map(
                static fn () => 'invalidArticle',
                array_fill(0, 10, null)
            )
        );
    }

    protected static function tearDownAfterClassByOzon(): void
    {
        static::$entity = null;
    }
}
