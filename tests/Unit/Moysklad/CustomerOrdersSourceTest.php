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

namespace CashCarryShop\Sizya\Tests\Unit\Moysklad;

use CashCarryShop\Sizya\Moysklad\CustomerOrdersSource;
use CashCarryShop\Sizya\Tests\Traits\OrdersGetterTests;
use CashCarryShop\Sizya\Tests\Traits\OrdersGetterByAdditionalTests;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithMoysklad;
use CashCarryShop\Sizya\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тестирования класса для получения заказов МойСклад.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(CustomerOrdersSource::class)]
class CustomerOrdersSourceTest extends TestCase
{
    use OrdersGetterTests;
    use OrdersGetterByAdditionalTests;
    use InteractsWithMoysklad;

    /**
     * Сущность.
     *
     * @var ?CustomerOrdersSource
     */
    protected static ?CustomerOrdersSource $entity = null;

    public static function setUpBeforeClass(): void
    {
        static::$entity  = new CustomerOrdersSource([
            'credentials' => ['login', 'password'],
            'client'      => static::createHttpClient(static::$handler),
            'limit'       => 100
        ]);
    }

    protected function createOrdersGetter(): ?CustomerOrdersSource
    {
        return static::$entity;
    }

    protected function createOrdersGetterByAdditional(): ?CustomerOrdersSource
    {
        return static::$entity;
    }

    protected function setUpBeforeTestGetOrders(): void
    {
        static::$handler->append(
            static::createMethodResponse('1.2/entity/customerorder')
        );
    }

    protected function ordersIdsProvider(): array
    {
        [
            'provides' => $ids,
            'invalid'  => $invalidIds
        ] = static::generateProvideData([
            'additionalInvalid' => \array_map(
                static fn () => 'validationErrorId',
                \array_fill(0, \random_int(5, 10), null)
            )
        ]);

        static::$handler->append(
            ...\array_fill(
                0,
                \count($ids),
                static::createMethodResponse('1.2/entity/customerorder', [
                    'captureItems' => function (&$items) use ($invalidIds) {
                        foreach ($items as $idx => $item) {
                            if (\in_array($item['id'], $invalidIds)) {
                                unset($items[$idx]);
                            }
                        }
                    }
                ])
            )
        );

        return $ids;
    }

    protected function ordersAdditionalProvider(): array
    {
        [
            'values'  => $values,
            'invalid' => $invalid
        ] = static::generateProvideData();

        static::$handler->append(
            static::createMethodResponse('1.2/entity/customerorder', [
                'captureItems' => function (&$items) use ($invalid) {
                    foreach ($items as $idx => $item) {
                        if (\in_array($item['attributes'][0]['value'], $invalid)) {
                            unset($items[$idx]);
                        }
                    }
                }
            ])
        );

        return [
            [
                static::guidv4(),
                $values
            ]
        ];
    }

    public static function tearDownAfterClass(): void
    {
        static::$entity = null;
    }
}
