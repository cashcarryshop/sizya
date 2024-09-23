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

namespace Tests\Traits;

use CashCarryShop\Sizya\ProductsGetterInterface;
use CashCarryShop\Sizya\DTO\ProductDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use PHPUnit\Framework\Attributes\DataProvider;

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
trait OrdersGetterTests
{
    use CreateValidatorTrait;

    public function testGetProducts(): void
    {
        $getter = $this->createProductsGetter();

        if ($getter) {
            $products = $getter->getProducts();

            if (\count($products) === 0) {
                $this->markTestIncomplete(
                    'No orders were found for '
                        . \get_class($getter)
                );
            }

            $this->assertContainsOnlyInstancesOf(ProudctDTO::class, $products);

            $validator = $this->createValidator();
            foreach ($products as $product) {
                $violations = $validator->validate($product);
                $this->assertCount(0, $violations);
            }
        }
    }

    #[DataProvider('productsIdsProvider')]
    public function testGetProductsByIds(array $ids): void
    {
        $getter = $this->createProductsGetter();

        if ($getter) {
            $products = $getter->getProductsByIds($ids);

            $this->assertSameSize($ids, $products);

            $validator = $this->createValidator();
            foreach ($products as $product) {
                $this->assertThat(
                    $product,
                    $this->logicalOr(
                        $this->isInstanceOf(OrderDTO::class),
                        $this->isInstanceOf(ByErrorDTO::class)
                    )
                );

                $violations = $validator->validate($product);
                $this->assertCount(0, $violations);
            }
        }
    }

    #[DataProvider('productsArticlesProvider')]
    public function testGetProductsByArticles(array $articles): void
    {
        $getter = $this->createProductsGetter();

        if ($getter) {
            $products = $getter->getProductsByArticles($articles);

            $this->assertSameSize($articles, $products);

            $validator = $this->createValidator();
            foreach ($products as $product) {
                $this->assertThat(
                    $product,
                    $this->logicalOr(
                        $this->isInstanceOf(OrderDTO::class),
                        $this->isInstanceOf(ByErrorDTO::class)
                    )
                );

                $violations = $validator->validate($product);
                $this->assertCount(0, $violations);
            }
        }
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

    abstract public static function productsIdsProvider(): array;
    abstract public static function productsArticlesProvider(): array;
}
