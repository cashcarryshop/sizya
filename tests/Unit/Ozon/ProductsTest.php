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
    protected static ?Products $entity = null;

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
        return static::$entity;
    }

    protected function setUpBeforeTestGetProducts(): void
    {
        static::$handler->append(
            ...static::makeProductsGetResponses(
                \array_map(
                    fn () => static::guidv4(),
                    \array_fill(0, 100, null)
                )
            )
        );
    }

    protected function productsIdsProvider(): array
    {
        [
            'provides' => $ids,
            'invalid'  => $invalidIds
        ] = static::generateProvideData();

        static::$handler->append(...static::makeProductsGetByIdsResponses($ids, $invalidIds));

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

        // Получение шабллонов
        $template    = static::getResponseData("api-seller.ozon.ru/v2/product/info/list")['body'];
        $templateRow = $template['result']['items'][0];

        $makeRow = function ($article) use ($templateRow, $invalidArticles) {
            if (\in_array($article, $invalidArticles)) {
                return null;
            }

            return static::makeProduct([
                'article'  => $article,
                'template' => $templateRow
            ]);
        };

        static::$handler->append(...\array_map(
            function ($articles) use ($template, $makeRow) {
                $template['result']['items'] = \array_filter(
                    \array_map($makeRow, \array_unique($articles)),
                    'is_array'
                );

                return static::createJsonResponse(body: $template);
            },
            $articles
        ));

        return $articles;
    }

    public static function tearDownAfterClass(): void
    {
        static::$entity = null;
    }
}
