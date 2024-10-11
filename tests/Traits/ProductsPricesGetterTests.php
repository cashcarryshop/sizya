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
        [
            'values'  => $ids,
            'valid'   => $validIds,
            'invalid' => $invalidIds
        ] = static::generateFakeData();

        $getter = $this->createProductsPricesGetter();

        $expectedProductsPrices = [];
        $expectedErrors   = [];
        $expected         = \array_map(
            function ($id) use (
                $invalidIds,
                &$expectedProductsPrices,
                &$expectedErrors
            ) {
                if (\in_array($id, $invalidIds)) {
                    return $expectedErrors[] = ByErrorDTO::fromArray([
                        'type'  => ByErrorDTO::NOT_FOUND,
                        'value' => $id
                    ]);
                }

                return $expectedProductsPrices[] =
                    static::fakeProductPricesDto(['id' => $id]);
            },
            $ids
        );

        $this->setUpBeforeTestGetProductsPricesByIds(
            $expectedProductsPrices,
            $expectedErrors,
            $expected
        );

        $this->assertProductsPrices(
            $expected, $getter->getProductsPricesByIds(
                \array_merge(
                    \array_column($expectedProductsPrices, 'id'),
                    \array_column($expectedErrors, 'value')
                )
            )
        );
    }

    public function testGetProductsPricesByArticles(): void
    {
        [
            'values'  => $articles,
            'valid'   => $validArticles,
            'invalid' => $invalidArticles
        ] = static::generateFakeData([
            'validGenerator' => static fn () => static::fakeArticle()
        ]);

        $getter = $this->createProductsPricesGetter();

        $expectedProductsPrices = [];
        $expectedErrors   = [];
        $expected         = \array_map(
            function ($article) use (
                $invalidArticles,
                &$expectedProductsPrices,
                &$expectedErrors
            ) {
                if (\in_array($article, $invalidArticles)) {
                    return $expectedErrors[] = ByErrorDTO::fromArray([
                        'type'  => ByErrorDTO::NOT_FOUND,
                        'value' => $article
                    ]);
                }

                return $expectedProductsPrices[] =
                    static::fakeProductPricesDto(['article' => $article]);
            },
            $articles
        );

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

    protected function setUpBeforeTestGetProductsPricesByIds(
        array $expectedProductsPrices,
        array $expectedErrors,
        array $expected
    ): void {
        // ...
    }

    protected function setUpBeforeTestGetProductsPricesByArticles(
        array $expectedProductsPrices,
        array $expectedErrors,
        array $expected
    ): void {
        // ...
    }
}
