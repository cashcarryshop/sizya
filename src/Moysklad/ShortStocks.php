<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
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
use CashCarryShop\Sizya\DTO\StockDTO;
use CashCarryShop\Sizya\DTO\ProductDTO;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Элемент для синхронизации остатков МойСклад.
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
            'stockType'    => 'quantity'
        ];

        parent::__construct(\array_replace($defaults, $settings));

        $this->settings['products'] = new Products([
            'credentials' => $this->getSettings('credentials'),
            'client'      => $this->getSettings('client')
        ]);
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
                ]
            ]
        );
    }

    /**
     * Получить остатки
     *
     * @see StocksGetterInterface
     *
     * @return StockDTO[]
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
        unset($since);

        $result = $this->decode($this->send($builder->build('GET')))->wait();

        $assortmentIds = \array_column($result, 'assortmentId');

        $products = \array_filter(
            $this->getSettings('products')
                ->getProductsByIds(
                    \array_unique(
                        $assortmentIds,
                        SORT_STRING
                    )
                ),
            static function ($item) {
                if ($item instanceof ProductDTO) {
                    return true;
                }

                if (in_array($item->type, ['internal', 'http'])) {
                    throw $item->reason;
                }

                return false;
            }
        );
        $productsIds = \array_column($products, 'id');

        \asort($assortmentIds, SORT_STRING);
        \asort($productsIds,   SORT_STRING);

        $stocks = [];

        \reset($assortmentIds);
        foreach ($productsIds as $idx => $productId) {
            if (\current($assortmentIds) === $productId) {
                do {
                    $item = $result[\key($assortmentIds)];

                    $stocks[] = StockDTO::fromArray([
                        'id'          => $productId,
                        'article'     => $products[$idx]?->article,
                        'warehouseId' => $item['storeId'],
                        'quantity'    => $item[$stockType] < 0 ? 0 : $item[$stockType],
                        'original'    => [
                            'stock'   => $item,
                            'product' => $products[$idx]
                        ]
                    ]);

                    \next($assortmentIds);
                } while (\current($assortmentIds) === $productId);
            }
        }

        return $stocks;
    }
}
