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

namespace CashCarryShop\Sizya\Tests\Unit\Synchronizer;

use CashCarryShop\Sizya\Synchronizer\ProductsPricesSynchronizer;
use CashCarryShop\Sizya\Tests\Synchronizer\MockProductsPricesSource;
use CashCarryShop\Sizya\Tests\Synchronizer\MockProductsPricesTarget;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithFakeData;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирование класса ProductsPricesSynchronizer.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(ProductsPricesSynchronizer::class)]
class ProductsPricesSynchronizerTest extends TestCase
{
    use InteractsWithFakeData;

    public function testSynchronize(): void
    {
        $source = new MockProductsPricesSource;
        $target = new MockProductsPricesTarget([
            'articles' => $source->settings['articles']
        ]);

        $relations = \array_map(
            static fn ($sourceId, $targetId) => [
                'source' => $sourceId,
                'target' => $targetId
            ],
            $sourceIds = $source->settings['pricesIds'],
            $targetIds = $target->settings['pricesIds']
        );

        $kRelations = \array_combine($sourceIds, $targetIds);

        $synchronizer = new ProductsPricesSynchronizer($source, $target);
        $synchronizer->synchronize([
            'throw'     => true,
            'relations' => $relations
        ]);

        \array_multisort(
            \array_column($source->settings['items'], 'article'),
            SORT_STRING,
            $source->settings['items']
        );

        \array_multisort(
            \array_column($target->settings['items'], 'article'),
            SORT_STRING,
            $target->settings['items']
        );

        \reset($target->settings['items']);
        foreach ($source->settings['items'] as $sProductPrices) {
            $tProductPrices = \current($target->settings['items']);

            $this->assertEquals(
                $sProductPrices->article,
                $tProductPrices->article,
                'Products prices articles does not equals'
            );

            $kTargetPrices = \array_combine(
                \array_column($tProductPrices->prices, 'id'),
                $tProductPrices->prices
            );

            foreach ($sProductPrices->prices as $sPrice) {
                $this->assertEquals(
                    $sPrice->value,
                    $kTargetPrices[$kRelations[$sPrice->id]]->value,
                    sprintf(
                        'Prices values with relation [%s->%s] does not equals',
                        $sPrice->id,
                        $kRelations[$sPrice->id]
                    )
                );
            }

            \next($target->settings['items']);
        }
    }
}
