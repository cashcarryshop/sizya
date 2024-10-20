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

use CashCarryShop\Sizya\DTO\DTOInterface;
use CashCarryShop\Sizya\DTO\{ProductDTO, ProductPricesDTO};
use CashCarryShop\Sizya\DTO\PriceDTO;
use CashCarryShop\Sizya\DTO\{OrderDTO, OrderCreateDTO, OrderUpdateDTO};
use CashCarryShop\Sizya\DTO\{AdditionalDTO, AdditionalCreateDTO, AdditionalUpdateDTO};
use CashCarryShop\Sizya\DTO\{PositionDTO, PositionCreateDTO};

/**
 * Трейт с методами для генерации фейковых данных.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait InteractsWithFakeData
{
    /**
     * Сгенерировать массивы с данными для dataProvider.
     *
     * Массив $options принимает:
     *
     * - validSize:         (int)      Размер валидных значений (по-умолчанию 30)
     * - invalidSize        (int)      Размер неверных значений (по-умолчанию 30)
     * - validGenerator:    (callable) Генератор валидных значений
     *                                 (по-умаолчанию через static::guidv4)
     * - invalidGenerator:  (callable) Генератор неверных значений
     *                                 (по-умаолчанию устанавливается validGenerator)
     *
     * @param array $options Опции
     *
     * @return array<mixed[], mixed[]> Возвращает общий массив и неверные значения
     */
    protected static function generateFakeData(array $options = []): array
    {
        $options = \array_replace(
            [
                'validSize'         => 10,
                'invalidSize'       => 10,
                'validGenerator'    => fn () => static::guidv4(),
                'invalidGenerator'  => $options['validGenerator'] ?? fn () => static::guidv4(),
            ], $options
        );

        $validValues = \array_map(
            $options['validGenerator'],
            \array_fill(0, $options['validSize'], null)
        );

        $invalidValues = \array_map(
            $options['invalidGenerator'],
            \array_fill(0, $options['invalidSize'], null)
        );

        $values = \array_merge($validValues, $invalidValues);
        \shuffle($values);

        return [
            'values'   => $values,
            'valid'    => $validValues,
            'invalid'  => $invalidValues,
        ];
    }

    /**
     * Создать фейковый DTO товара.
     *
     * @param array $options Опции
     *
     * @return ProductDTO
     */
    protected static function fakeProductDto($options = []): ProductDTO
    {
        return ProductDTO::fromArray([
            'id'      => $options['id'] ?? static::guidv4(),
            'article' => $options['article'] ?? static::fakeArticle(),
            'type'    => $options['type'] ??
                \random_int(0, 3) === 3 ? 'product' : 'variant',
            'created' => static::fakeDtoDate(),
            'prices'  => $options['prices'] ?? [
                static::fakePriceDto(),
                static::fakePriceDto(),
                static::fakePriceDto([
                    'id'   => 'minPrice',
                    'name' => 'Min price'
                ])
            ]
        ]);
    }

    /**
     * Создать фейковый DTO цен товара.
     *
     * @param array $options Опции
     *
     * @return ProductPricesDTO
     */
    protected static function fakeProductPricesDto($options = []): ProductPricesDTO
    {
        return ProductPricesDTO::fromArray([
            'id'      => $options['id'] ?? static::guidv4(),
            'article' => $options['article'] ?? static::fakeArticle(),
            'prices'  => $options['prices'] ?? [
                static::fakePriceDto(),
                static::fakePriceDto(),
                static::fakePriceDto([
                    'id'   => 'minPrice',
                    'name' => 'Min price'
                ])
            ]
        ]);
    }

    /**
     * Создать фейковый объект PriceDTO.
     *
     * @param array $options Опции
     *
     * @return PriceDTO
     */
    protected static function fakePriceDto(array $options = []): PriceDTO
    {
        return PriceDTO::fromArray([
            'id'    => $options['id']   ?? static::guidv4(),
            'name'  => $options['name'] ?? static::fakeArticle(),
            'value' => (float) \random_int(0, 10000)
        ]);
    }

    /**
     * Создать ProductDTO из ProductPricesDTO
     *
     * @param ProductPricesDTO $productPrices Цены товара
     *
     * @return ProductDTO
     */
    protected static function fakeProductDtoFromProductPrices(ProductPricesDTO $productPrices): ProductDTO
    {
        return static::fakeProductDto([
            'id'      => $productPrices->id,
            'article' => $productPrices->article,
            'prices'  => $productPrices->prices
        ]);
    }

    /**
     * Создать фейковый DTO заказа.
     *
     * @param array $options Опции
     *
     * @return OrderDTO
     */
    protected static function fakeOrderDto($options = []): OrderDTO
    {
        return OrderDTO::fromArray([
            'id'             => $options['id'] ?? static::guidv4(),
            'created'        => static::fakeDtoDate(),
            'status'         => static::guidv4(),
            'externalCode'   => \sha1(static::guidv4()),
            'shipmentDate'   => \random_int(0, 3) === 3
                ? null
                : static::fakeDtoDate(),
            'deliveringDate' => \random_int(0, 3) === 3
                ? null
                : static::fakeDtoDate(),
            'description' => static::fakeString(),
            'additionals' => \array_map(
                fn () => AdditionalDTO::fromArray([
                    'id'       => static::guidv4(),
                    'entityId' => static::guidv4(),
                    'name'     => static::fakeArticle(),
                    'value'    => static::fakeString()
                ]),
                \array_fill(0, 3, null)
            ),
            'positions' => \array_map(
                fn () => PositionDTO::fromArray([
                    'id'        => static::guidv4(),
                    'productId' => static::guidv4(),
                    'article'   => static::fakeArticle(),
                    'type'      => \random_int(0, 3) === 3 ? 'product' : 'variant',
                    'quantity'  => $quantity = \random_int(0, 10),
                    'reserve'   => \random_int(0, $quantity),
                    'price'     => (float) \random_int(0, 10000),
                    'discount'  => (float) \random_int(0, 50),
                    'currency'  => 'RUB',
                    'vat'       => \random_int(0, 3) === 1
                ]),
                \array_fill(0, 3, null)
            )
        ]);
    }

    /**
     * Создать OrderCreateDTO из OrderDTO
     *
     * @param OrderDTO $order Заказ
     *
     * @return OrderCreateDTO
     */
    protected static function fakeOrderCreateDtoFromOrder(OrderDTO $order): OrderCreateDTO
    {
        return OrderCreateDTO::fromArray([
            'created'        => $order->created,
            'status'         => $order->status,
            'shipmentDate'   => $order->shipmentDate,
            'deliveringDate' => $order->deliveringDate,
            'description'    => $order->description,
            'additionals'    => \array_map(
                fn ($additional) => AdditionalCreateDTO::fromArray([
                    'entityId' => $additional->entityId,
                    'value'    => $additional->value
                ]),
                $order->additionals
            ),
            'positions' => \array_map(
                fn ($position) => PositionCreateDTO::fromArray([
                    'productId' => $id = (
                        \random_int(0, 3) === 3
                            ? $position->productId
                            : null
                    ),
                    'article'  => $id ? (
                        \random_int(0, 3) === 3
                            ? $position->article
                            : null
                    ) : $position->article,
                    'type'     => \random_int(0, 3) === 3 ? 'product' : 'variant',
                    'quantity' => $position->quantity,
                    'reserve'  => $position->reserve,
                    'price'    => $position->price,
                    'discount' => $position->discount,
                    'currency' => $position->currency
                ]),
                $order->positions
            )
        ]);
    }

    /**
     * Создать OrderCreateDTO из OrderDTO
     *
     * @param OrderDTO $order Заказ
     *
     * @return OrderUpdateDTO
     */
    protected static function fakeOrderUpdateDtoFromOrder(OrderDTO $order): OrderUpdateDTO
    {
        return OrderUpdateDTO::fromArray([
            'id'             => $order->id,
            'created'        => $order->created,
            'status'         => $order->status,
            'shipmentDate'   => $order->shipmentDate,
            'deliveringDate' => $order->deliveringDate,
            'description'    => $order->description,
            'additionals'    => \array_map(
                fn ($additional) => AdditionalUpdateDTO::fromArray([
                    'id'       => $additional->id,
                    'entityId' => $additional->entityId,
                    'value'    => $additional->value
                ]),
                $order->additionals
            )
        ]);
    }

    /**
     * Сгенерировать guidv4
     *
     * @param ?string $data Битовые данные длинной 128 битов
     *
     * @return string
     */
    protected static function guidv4(?string $data = null): string
    {
        // Generate 16 bytes (128 bits) of random data
        // or use the data passed into the function.
        $data = $data ?? \random_bytes(16);
        assert(\strlen($data) == 16);

        // Set version to 0100
        $data[6] = \chr(\ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = \chr(\ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return \vsprintf('%s%s-%s-%s-%s-%s%s%s', \str_split(\bin2hex($data), 4));
    }

    /**
     * Сгенерировать фейковый артикул.
     *
     * @param string $prefix Префикс
     * @param ?int   $index  Индекс
     * @param int    $length Длина
     *
     * @return string
     */
    protected static function fakeArticle(
        string $prefix = 'CCS',
        ?int   $index  = null,
        int    $length = 5
    ): string {
        return $prefix . \str_pad(
            $index ?? (string) \random_int(0, 1000),
            $length,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Сгенерировать рандомную строку.
     *
     * @param int $length Длина строки
     *
     * @return string
     */
    protected static function fakeString(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = \strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[\random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Сгенерировать рандомную дату для dto.
     *
     * @return string
     */
    protected static function fakeDtoDate(): string
    {
        return \date(DTOInterface::DATE_FORMAT, \mt_rand(1, \time()));
    }

    /**
     * Сгенерировать фейковую дату в формате Y-m-d H:i:s.
     *
     * @return string
     */
    protected static function fakeDate(): string
    {
        return \date('Y-m-d H:i:s', \mt_rand(1, \time()));
    }
}
