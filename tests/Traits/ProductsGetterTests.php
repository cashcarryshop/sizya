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
use CashCarryShop\Sizya\DTO\ProductDTO;
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
    use CreateValidatorTrait;

    public function testGetProducts(): void
    {
        $getter = $this->createProductsGetter();

        if ($getter) {
            $this->setUpBeforeTestGetProducts();
            $products = $getter->getProducts();

            if (\count($products) === 0) {
                $this->markTestIncomplete(
                    'No products were found for '
                        . \get_class($getter)
                );
            }

            $this->assertContainsOnlyInstancesOf(ProductDTO::class, $products);

            $validator = $this->createValidator();
            foreach ($products as $product) {
                $violations = $validator->validate($product);
                $this->assertCount(0, $violations, (string) $violations);
            }

            return;
        }

        $this->markTestIncomplete('Products getter is null');
    }

    public function testGetProductsByIds(): void
    {
        $getter = $this->createProductsGetter();

        if ($getter) {
            foreach ($this->productsIdsProvider() as $ids) {
                $products = $getter->getProductsByIds($ids);

                if (\count($products) === 0) {
                    $this->markTestIncomplete(
                        'No products were found for '
                            . \get_class($getter)
                    );
                }

                $validator = $this->createValidator();
                foreach ($products as $product) {
                    $this->assertThat(
                        $product,
                        $this->logicalOr(
                            $this->isInstanceOf(ProductDTO::class),
                            $this->isInstanceOf(ByErrorDTO::class)
                        )
                    );

                    $violations = $validator->validate($product);
                    $this->assertCount(0, $violations, (string) $violations);
                }

                $this->assertSameSize($ids, $products);
            }

            return;
        }

        $this->markTestIncomplete('Products getter is null');
    }

    public function testGetProductsByArticles(): void
    {
        $getter = $this->createProductsGetter();

        if ($getter) {
            foreach ($this->productsArticlesProvider() as $articles) {
                $products = $getter->getProductsByArticles($articles);

                if (\count($products) === 0) {
                    $this->markTestIncomplete(
                        'No products were found for '
                            . \get_class($getter)
                    );
                }

                $validator = $this->createValidator();
                foreach ($products as $product) {
                    $this->assertThat(
                        $product,
                        $this->logicalOr(
                            $this->isInstanceOf(ProductDTO::class),
                            $this->isInstanceOf(ByErrorDTO::class)
                        )
                    );

                    $violations = $validator->validate($product);
                    $this->assertCount(0, $violations, (string) $violations);
                }

                $this->assertSameSize($articles, $products);
            }

            return;
        }

        $this->markTestIncomplete('Products getter is null');
    }

    protected static function generateIds(array $products, array $invalidIds): array
    {
        $ids = \array_merge(
            \array_map(
                static fn ($product) => $product->id,
                $products
            ),
            $invalidIds
        );

        \shuffle($ids);

        return \array_map(
            static fn ($chunk) => [$chunk],
            \array_chunk($ids, 30)
        );
    }

    protected static function generateArticles(array $products, array $invalidArticles): array
    {
        $ids = \array_merge(
            \array_map(
                static fn ($product) => $product->article,
                $products
            ),
            $invalidArticles
        );

        \shuffle($ids);

        return \array_map(
            static fn ($chunk) => [$chunk],
            \array_chunk($ids, 30)
        );
    }


    abstract protected function createProductsGetter(): ?ProductsGetterInterface;

    abstract protected function productsIdsProvider(): array;
    abstract protected function productsArticlesProvider(): array;

    protected function setUpBeforeTestGetProducts(): void
    {
        // ...
    }
}
