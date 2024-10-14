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

use CashCarryShop\Sizya\ProductsPricesUpdaterInterface;
use CashCarryShop\Sizya\DTO\ProductPricesUpdateDTO;
use CashCarryShop\Sizya\DTO\PriceUpdateDTO;

/**
 * Трейт с тестами для обновления цен товаров.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see ProductsPricesUpdaterInterface
 */
trait ProductsPricesUpdaterTests
{
    use InteractsWithFakeData;
    use ProductsPricesAssertions;

    public function testUpdateProductsPrices(): void
    {
        $updater = $this->createProductsPricesUpdater();

        $expected = \array_map(
            fn () => static::fakeProductPricesDto(),
            \array_fill(0, 10, null)
        );

        $this->setUpBeforeTestUpdateProductsPrices($expected);

        $this->assertProductsPrices(
            $expected,
            $updater->updateProductsPrices(
                \array_map(
                    fn ($productPrices) => ProductPricesUpdateDTO::fromArray([
                        'id' => $id = \random_int(0, 3) === 3
                            ? $productPrices->id
                            : null,
                        'article' => $id
                            ? (
                                \random_int(0, 2) === 2
                                    ? $productPrices->article
                                    : null
                            )
                            : $productPrices->article,
                        'prices' => \array_map(
                            fn ($price) => PriceUpdateDTO::fromArray([
                                'id'    => $price->id,
                                'value' => $price->value
                            ]),
                            $productPrices->prices
                        )
                    ]),
                    $expected
                )
            )
        );
    }

    abstract protected function createProductsPricesUpdater(): ProductsPricesUpdaterInterface;

    protected function setUpBeforeTestUpdateProductsPrices(
        array $expected,
        array $forUpdate
    ): void {
        // ...
    }
}
