<?php
/**
 * Этот файл является частью пакета sizya
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
use Symfony\Component\Validator\Constraints as Assert;

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
     * Создать объект для работы с
     * коротким отчетом об остатках
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        $defaults = [
            'changedSince' => null,
            'stockType'    => 'quantity',
            'products'     => null
        ];

        parent::__construct(array_replace($defaults, $settings));

        $this->settings['products'] = $this->getSettings('products', new Products([
            'credentials' => $this->getCredentials(),
            'client' => $this->getSettings('client')
        ]));
    }

    /**
     * Правила валидации для настроек
     *
     * @return array
     */
    protected function rules(): array
    {
        return array_merge(
            parent::rules(), [
                'changedSince' => [
                    new Assert\Type(['string', 'null']),
                    new Assert\When(
                        expression: 'value !== null',
                        constraints: [new Assert\DateTime('Y-m-d H:i:s')]
                    )
                ],
                'stockType' => [
                    new Assert\Type('string'),
                    new Assert\Choice([
                        'quantity',
                        'freeStock',
                        'reserve',
                        'inTransit',
                        'stock',
                    ]),
                ],
                'products' => new Assert\Type(['null', Products::class]),
            ]
        );
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
        $stockType = $this->getSettings('stockType');

        $builder = $this->builder()
            ->point("report/stock/bystore/current")
            ->param('stockType', $stockType);

        if ($since = $this->getSettings('changedSince')) {
            $builder->param('changedSince', $since);
        }

        $stocks = $this->decode($this->send($builder->build('GET')))->wait();

        $productIds = array_unique(array_column($stocks, 'assortmentId'));
        $products = $this->getSettings('products')->getProductsByIds($productIds);
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
