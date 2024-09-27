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
        $ids = \array_map(
            fn () => static::guidv4(),
            \array_fill(0, 100, null)
        );

        $template    = static::getResponseData("api.moysklad.ru/api/remap/1.2/entity/assortment")['body'];
        $templateRow = $template['rows'][0];

        $makeRow = fn ($id) => static::makeAssortmentItem([
            'id'       => $id,
            'type'     => \random_int(1, 3) === 2 ? 'variant' : 'product',
            'template' => $templateRow
        ]);

        static::$handler->append(function ($request) use ($template, $makeRow, $ids) {
            $template['rows'] = \array_map($makeRow, $ids);

            $template['meta']['href'] = (string) $request->getUri();
            $template["meta"]['size'] = \count($template['rows']);

            return static::createJsonResponse(body: $template);
        });
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
        $template    = static::getResponseData("api.moysklad.ru/api/remap/1.2/entity/assortment")['body'];
        $templateRow = $template['rows'][0];

        $makeRow = function ($article) use ($templateRow, $invalidArticles) {
            if (\in_array($article, $invalidArticles)) {
                return null;
            }

            return static::makeAssortmentItem([
                'article'  => $article,
                'code'     => $article,
                'type'     => \random_int(1, 3) === 2 ? 'variant' : 'product',
                'template' => $templateRow
            ]);
        };

        $makeResponse = function ($articles) use ($template, $makeRow) {
            $template['rows'] = \array_filter(
                \array_map($makeRow, \array_unique($articles)),
                'is_array'
            );

            $template['meta']['size'] = \count($template['rows']);

            return function ($request) use ($template) {
                $template['meta']['href'] = (string) $request->getUri();
                return static::createJsonResponse(body: $template);
            };
        };

        static::$handler->append(...\array_map($makeResponse, $articles));

        return $articles;
    }

    public static function tearDownAfterClass(): void
    {
        static::$entity = null;
    }
}
