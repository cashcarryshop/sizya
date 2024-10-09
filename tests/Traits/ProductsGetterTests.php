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

use CashCarryShop\Sizya\ProductsGetterInterface;
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
 * @see ProductsGetterInterface
 */
trait ProductsGetterTests
{
    use InteractsWithFakeData;
    use ProductsAssertions;

    public function testGetProducts(): void
    {
        $getter = $this->createProductsGetter();

        $expected = \array_map(
            fn () => static::fakeProductDto(),
            \array_fill(0, 10, null)
        );

        $this->setUpBeforeTestGetProducts($expected);

        $this->assertProducts($expected, $getter->getProducts());
    }

    public function testGetProductsByIds(): void
    {
        [
            'values'  => $ids,
            'valid'   => $validIds,
            'invalid' => $invalidIds
        ] = static::generateFakeData();

        $getter = $this->createProductsGetter();

        $expectedProducts = [];
        $expectedErrors   = [];
        $expected         = \array_map(
            function ($id) use (
                $invalidIds,
                &$expectedProducts,
                &$expectedErrors
            ) {
                if (\in_array($id, $invalidIds)) {
                    return $expectedErrors[] = ByErrorDTO::fromArray([
                        'type'  => ByErrorDTO::NOT_FOUND,
                        'value' => $id
                    ]);
                }

                return $expectedProducts[] =
                    static::fakeProductDto(['id' => $id]);
            },
            $ids
        );

        $this->setUpBeforeTestGetProductsByIds(
            $expectedProducts,
            $expectedErrors,
            $expected
        );

        $this->assertProducts($expected, $getter->getProductsByIds($ids));
    }

    public function testGetProductsByArticles(): void
    {
        [
            'values'  => $articles,
            'valid'   => $validArticles,
            'invalid' => $invalidArticles
        ] = static::generateFakeData([
            'validGenerator' => static fn () => static::fakeArticle()
        ]);

        $getter = $this->createProductsGetter();

        $expectedProducts = [];
        $expectedErrors   = [];
        $expected         = \array_map(
            function ($article) use (
                $invalidArticles,
                &$expectedProducts,
                &$expectedErrors
            ) {
                if (\in_array($article, $invalidArticles)) {
                    return $expectedErrors[] = ByErrorDTO::fromArray([
                        'type'  => ByErrorDTO::NOT_FOUND,
                        'value' => $article
                    ]);
                }

                return $expectedProducts[] =
                    static::fakeProductDto(['article' => $article]);
            },
            $articles
        );

        $this->setUpBeforeTestGetProductsByArticles(
            $expectedProducts,
            $expectedErrors,
            $expected
        );

        $this->assertProducts($expected, $getter->getProductsByArticles($articles));
    }

    abstract protected function createProductsGetter(): ProductsGetterInterface;

    protected function setUpBeforeTestGetProducts(array $expected): void
    {
        // ...
    }

    protected function setUpBeforeTestGetProductsByIds(
        array $expectedProducts,
        array $expectedErrors,
        array $expected
    ): void {
        // ...
    }

    protected function setUpBeforeTestGetProductsByArticles(
        array $expectedProducts,
        array $expectedErrors,
        array $expected
    ): void {
        // ...
    }
}
