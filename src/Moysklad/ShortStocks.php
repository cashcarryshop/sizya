<?php
/**
 * Элемент для синхронизации остатков МойСклад
 *
 * Передает остатки из "краткого отчета"
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Moysklad;

use CashCarryShop\Sizya\StocksGetterInterface;
use Respect\Validation\Validator as v;

/**
 * Элемент для синхронизации остатков МойСклад
 *
 * Передает остатки из "краткого отчета"
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class ShortStocks extends AbstractSource implements StocksGetterInterface
{
    /**
     * Объект для работы товарами МойСклад
     *
     * @var Products
     */
    public readonly Products $products;

    /**
     * Создать объект для работы с
     * коротким отчетом об остатках
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);
        v::allOf(
            v::key('changedSince', v::dateTime('Y-m-d H:i:s'), false),
            v::key('products', v::instance(Products::class), false),
            v::key(
                'stockType', v::stringType()->in([
                    'quantity',
                    'freeStock',
                    'reserve',
                    'inTransit',
                    'stock'
                ]), false
            ),
        )->assert($settings);

        $this->products = $this->getSettings('products', new Products([
            'credentials' => $this->getCredentials(),
            'client' => $this->getSettings('client')
        ]));
    }

    /**
     * Получить остатки
     *
     * Смотреть `StocksInterface::getStocks`
     *
     * @return array
     */
    public function getStocks(): array
    {
        $stockType = $this->getSettings('stockType', 'quantity');

        $builder = $this->builder()
            ->point("report/stock/bystore/current")
            ->param('stockType', $stockType);

        if ($since = $this->getSettings('changedSince')) {
            $builder->param('changedSince', $since);
        }

        $stocks = $this->decode($this->send($builder->build('GET')))->wait();

        $productIds = array_unique(array_column($stocks, 'assortmentId'));
        $products = $this->products->getProductsByIds($productIds);
        $productIds = array_column($products, 'id');

        $output = [];

        foreach ($stocks as $stock) {
            $index = array_search($stock['assortmentId'], $productIds);
            if ($index === false) {
                continue;
            }

            $output[] = [
                'id' => $stock['assortmentId'],
                'article' => $products[$index]['article'],
                'warehouse_id' => $stock['storeId'],
                'quantity' => $stock[$stockType],
                'original' => $stock
            ];
        }

        return $output;
    }
}
