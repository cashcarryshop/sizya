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

use CashCarryShop\Sizya\Moysklad\Products;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithMoysklad;
use CashCarryShop\Sizya\Tests\Traits\ProductsGetterTests;
use CashCarryShop\Sizya\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для получения товаров Moysklad.
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
    use InteractsWithMoysklad;
    use ProductsGetterTests;

    /**
     * Используемыая сущность.
     *
     * @var ?Products
     */
    protected static ?Products $entity = null;

    public static function setUpBeforeclass(): void
    {
        static::$entity = new Products([
            'credentials' => ['login', 'password'],
            'client'      => static::createHttpClient(static::$handler)
        ]);
    }

    protected function createProductsGetter(): ?Products
    {
        return static::$entity;
    }

    protected function setUpBeforeTestGetProducts(): void
    {
        static::$handler->append(
            static::createMethodResponse('1.2/entity/assortment')
        );
    }

    protected function productsIdsProvider(): array
    {
        [
            'provides' => $ids,
            'invalid'  => $invalidIds
        ] = static::generateProvideData([
            'additionalInvalid' => \array_map(
                static fn () => 'validationErrorId',
                \array_fill(0, \random_int(5, 10), null)
            )
        ]);

        static::$handler->append(
            static::createMethodResponse('1.2/entity/assortment', [
                'captureItems' => function (&$items) use ($invalidIds) {
                    foreach ($items as $idx => $item) {
                        if (\in_array($item['id'], $invalidIds)) {
                            unset($items[$idx]);
                        }
                    }
                }
            ]),
            static::createMethodResponse('1.2/entity/assortment', [
                'captureItems' => fn (&$items) => $items = []
            ])
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
            static::createMethodResponse('1.2/entity/assortment', [
                'captureItems' => function (&$items) use ($invalidArticles) {
                    foreach ($items as $idx => $item) {
                        $article = $item['meta']['type'] === 'product'
                            ? $item['article'] : $item['code'];

                        if (\in_array($article, $invalidArticles)) {
                            unset($items[$idx]);
                        }
                    }
                }
            ]),
            static::createMethodResponse('1.2/entity/assortment', [
                'captureItems' => fn (&$items) => $items = []
            ])
        );

        return $articles;
    }

    public static function tearDownAfterClass(): void
    {
        static::$entity = null;
    }
}
