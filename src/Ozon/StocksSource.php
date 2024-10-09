<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Ozon;

use CashCarryShop\Sizya\StocksGetterInterface;
use CashCarryShop\Sizya\DTO\StockDTO;
use GuzzleHttp\Promise\Utils as PromiseUtils;

/**
 * Элемент для синхронизации остатков Ozon
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class StocksSource extends AbstractStocks implements StocksGetterInterface
{
    /**
     * Получить остатки товаров
     *
     * @see StockDTO
     *
     * @return StockDTO[]
     */
    public function getStocks(): array
    {
        $products = $this->getSettings('products')->getProducts();

        $skus = \array_map(
            static fn ($product) => $product->original['sku'],
            $products
        );

        $builder = $this->builder()
            ->point('v1/product/info/stocks-by-warehouse/fbs');

        $chunks = PromiseUtils::all(
            \array_map(
                fn ($chunk) => $this->decode(
                    $this->send(
                        (clone $builder)
                            ->body(['sku' => $chunk])
                            ->build('POST')
                    )
                ),
                \array_chunk($skus, 500)
            )
        )->wait();

        $items = [];
        foreach ($chunks as $chunk) {
            $items = \array_merge($items, $chunk['result']);
        }
        unset($chunks);

        $itemsSkus = \array_map(
            static fn ($item) => $item['sku'],
            $items
        );

        \asort($skus,      SORT_REGULAR);
        \asort($itemsSkus, SORT_REGULAR);

        $stocks = [];
        \reset($itemsSkus);
        foreach ($skus as $idx => $sku) {
            if (\current($itemsSkus) === $sku) {
                do {
                    $stocks[] = StockDTO::fromArray([
                        'id'          => (string) $products[$idx]->id,
                        'article'     => $products[$idx]->article,
                        'warehouseId' => (string) $items[\key($itemsSkus)]['warehouse_id'],
                        'quantity'    => (int) $items[\key($itemsSkus)]['present'],
                        'original'    => [
                            'product' => $products[$idx],
                            'stock'   => $items[\key($itemsSkus)]
                        ]
                    ]);

                    \next($itemsSkus);
                } while (\current($itemsSkus) === $sku);

                continue;
            }

            // todo: Здесь должна быть какая-то обработка ошибок
        }

        return $stocks;
    }
}
