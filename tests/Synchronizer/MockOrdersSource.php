<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category TestMock
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Tests\Synchronizer;

use CashCarryShop\Sizya\OrdersGetterInterface;
use CashCarryShop\Sizya\OrdersGetterByAdditionalInterface;
use CashCarryShop\Sizya\OrdersGetterByExternalCodesInterface;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\AdditionalDTO;
use CashCarryShop\Sizya\DTO\PositionDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithFakeData;
use CashCarryShop\Synchronizer\SynchronizerSourceInterface;

/**
 * Тестовый класс источника синхронизации заказов.
 *
 * @category TestMock
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class MockOrdersSource
    implements SynchronizerSourceInterface,
               OrdersGetterInterface,
               OrdersGetterByAdditionalInterface,
               OrdersGetterByExternalCodesInterface
{
    use InteractsWithFakeData;

    /**
     * Настройки.
     *
     * @var array
     */
    public array $settings;

    /**
     * Создать экземпляр источника заказов.
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        $statuses = $settings['statuses'] ?? [
            'new',
            'archive',
            'delivering',
            'shipment',
        ];

        $countStatuses = \count($statuses) - 1;

        $additionalsIds = $settings['additionalsIds'] ?? \array_map(
            fn () => static::guidv4(),
            \array_fill(0, 3, null)
        );

        $products = $settings['products'] ?? \array_map(
            fn () => [
                'id'      => static::guidv4(),
                'article' => static::fakeArticle()
            ],
            \array_fill(0, 10, null)
        );

        $countProducts = \count($products) - 1;

        $this->settings = \array_replace(
            [
                'statuses'       => $statuses,
                'products'       => $products,
                'additionalsIds' => $additionalsIds,
                'items'          => \array_map(
                    fn () => OrderDTO::fromArray([
                        'id'           => $id = static::guidv4(),
                        'created'      => static::fakeDtoDate(),
                        'status'       => $statuses[\random_int(0, $countStatuses)],
                        'externalCode' => \sha1($id),
                        'testKey'      => \sha1(static::guidv4()),
                        'shipmentDate' => \random_int(0, 3) === 3
                            ? null
                            : static::fakeDtoDate(),
                        'deliveringDate' => \random_int(0, 3) === 3
                            ? null
                            : static::fakeDtoDate(),
                        'additionals' => \array_map(
                            fn ($id) => AdditionalDTO::fromArray([
                                'id'       => $id,
                                'entityId' => $id,
                                'name'     => static::fakeArticle(),
                                'value'    => static::fakeString()
                            ]),
                            $additionalsIds
                        ),
                        'positions' => \array_map(
                            function () use ($products, $countProducts) {
                                $product = $products[\random_int(0, $countProducts)];
                                $quantity = \random_int(1, 10);

                                $product = PositionDTO::fromArray([
                                    'id'        => static::guidv4(),
                                    'type'      => 'product',
                                    'productId' => $product['id'],
                                    'article'   => $product['article'],
                                    'quantity'  => $quantity,
                                    'reserve'   => \random_int(0, $quantity),
                                    'price'     => (float) \random_int(0, 10000),
                                    'discount'  => (float) \random_int(0, 50),
                                    'currency'  => 'RUB',
                                    'vat'       => \random_int(0, 1) === 0
                                ]);

                                $product->original = &$product;

                                return $product;
                            },
                            \array_fill(1, 5, null)
                        )
                    ]),
                    \array_fill(0, 20, null)
                )
            ],
            $settings
        );

        $this->settings['items']    = \array_values($this->settings['items']);
        $this->settings['products'] = \array_values($this->settings['products']);
    }

    /**
     * Получить заказы
     *
     * @see OrderDTO
     *
     * @return OrderDTO[]
     */
    public function getOrders(): array
    {
        return $this->settings['items'];
    }

    /**
     * Получить заказы по идентификаторам
     *
     * Количество возвращаемых элементов должно
     * соответствовать переданным.
     *
     * @param array<string> $ordersIds Идентификаторы заказов
     *
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return array<int, OrderDTO|ByErrorDTO>
     */
    public function getOrdersByIds(array $ordersIds): array
    {
        $orders = [];
        $itemsIds = \array_column($this->settings['items'], 'id');
        foreach ($ordersIds as $id) {
            $key = \array_search($id, $itemsIds);
            if ($key === false) {
                $orders[] = ByErrorDTO::fromArray([
                    'type'  => ByErrorDTO::NOT_FOUND,
                    'value' => $id
                ]);
                continue;
            }

            $orders[] = $this->settings['items'][$key];
        }

        return $orders;
    }

    /**
     * Получить заказ по идентификатору
     *
     * @param string $orderId Идентификатор заказа
     *
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return OrderDTO|ByErrorDTO
     */
    public function getOrderById(string $orderId): OrderDTO|ByErrorDTO
    {
        return $this->getOrdersByIds([$orderId])[0];
    }

    /**
     * Получить заказы по доп. полю
     *
     * @param string            $entityId Идентификатор сущности
     * @param array<int, mixed> $values   Значения доп. поля
     *
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return array<int, OrderDTO|ByErrorDTO>
     */
    public function getOrdersByAdditional(string $entityId, array $values): array
    {
        $orders = [];
        foreach ($values as $value) {
            foreach ($this->settings['items'] as $item) {
                foreach ($item->additionals as $additional) {
                    if ($additional->entityId === $entityId
                        && $additional->value === $value
                    ) {
                        $orders[] = $item;
                        continue 3;
                    }
                }
            }

            $orders[] = ByErrorDTO::fromArray([
                'type'  => ByErrorDTO::NOT_FOUND,
                'value' => $value
            ]);
        }

        return $orders;
    }

    /**
     * Получить заказы по внешним кодам.
     *
     * Количество возвращаемых элементов должно
     * соответствовать переданным.
     *
     * @param string[] $codes Внешние коды
     *
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return array<int, OrderDTO|ByErrorDTO>
     */
    public function getOrdersByExternalCodes(array $codes): array
    {
        $orders = [];
        $itemsCodes = \array_column($this->settings['items'], 'externalCode');
        foreach ($codes as $code) {
            $key = \array_search($code, $itemsCodes);
            if ($key === false) {
                $orders[] = ByErrorDTO::fromArray([
                    'type'  => ByErrorDTO::NOT_FOUND,
                    'value' => $code
                ]);
                continue;
            }

            $orders[] = $this->settings['items'][$key];
        }

        return $orders;
    }
}
