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
        $template    = static::getResponseData("api.moysklad.ru/api/remap/1.2/entity/customerorder")['body'];
        $templateRow = $template['rows'][0];
        $templateAssortment = static::getResponseData(
            'api.moysklad.ru/api/remap/1.2/entity/assortment'
        )['body']['rows'][0];

        $templatePosition = static::getResponseData(
            'api.moysklad.ru/api/remap/1.2/entity/$category/$entityId/positions/$id'
        )['body'];

        static::$handler->append(function ($request) use (
            $template,
            $templateRow,
            $templatePosition,
            $templateAssortment,
        ) {
            $template['rows'] = \array_map(
                function () use (
                    $templateRow,
                    $templatePosition,
                    $templateAssortment
                ) {
                    return static::makeCustomerOrder([
                        'template'           => $templateRow,
                        'positionTemplate'   => $templatePosition,
                        'assortmentTemplate' => $templateAssortment
                    ]);
                },
                \array_fill(0, 100, null)
            );

            $template['meta']['href'] = (string) $request->getUri();
            $template["meta"]['size'] = \count($template['rows']);

            return static::createJsonResponse(body: $template);
        });
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

        // Получение шаблонов.
        $template = static::getResponseData("api.moysklad.ru/api/remap/1.2/entity/customerorder")['body'];

        $templateRow = $template['rows'][0];

        $templateAssortment = static::getResponseData(
            'api.moysklad.ru/api/remap/1.2/entity/assortment'
        )['body']['rows'][0];

        $templatePosition = static::getResponseData(
            'api.moysklad.ru/api/remap/1.2/entity/$category/$entityId/positions/$id'
        )['body'];

        // Создание ответов.
        $makeRow = function ($id) use (
            $templateRow,
            $templatePosition,
            $templateAssortment,
            $invalidIds
        ) {
            if (\in_array($id, $invalidIds)) {
                return null;
            }

            return static::makeCustomerOrder([
                'id'                 => $id,
                'template'           => $templateRow,
                'positionTemplate'   => $templatePosition,
                'assortmentTemplate' => $templateAssortment
            ]);
        };

        $makeResponse = function ($ids) use ($template, $makeRow) {
            $template['rows'] = \array_filter(
                \array_map($makeRow, $ids),
                'is_array'
            );

            $template["meta"]['size'] = \count($template['rows']);

            return function ($request) use ($template) {
                $template['meta']['href'] = (string) $request->getUri();
                return static::createJsonResponse(body: $template);
            };
        };

        static::$handler->append(...\array_map($makeResponse, $ids));

        return $ids;
    }

    protected function ordersAdditionalProvider(): array
    {
        [
            'values' => $values,
            'valid'  => $validValues
        ] = static::generateProvideData();

        $entityId = static::guidv4();

        // Получение необходимых шаблонов для ответа.
        $template = static::getResponseData('api.moysklad.ru/api/remap/1.2/entity/customerorder')['body'];
        $templateRow        = $template['rows'][0];
        $templateAttribute  = $template['rows'][0]['attributes'][0];
        $templateAssortment = static::getResponseData(
            'api.moysklad.ru/api/remap/1.2/entity/assortment'
        )['body']['rows'][0];
        $templatePosition = static::getResponseData(
            'api.moysklad.ru/api/remap/1.2/entity/$category/$entityId/positions/$id'
        )['body'];

        // Создание Response
        static::$handler->append(
            static::createResponse(404),
            function ($request) use (
                $entityId,
                $template,
                $templateRow,
                $templateAttribute,
                $templateAssortment,
                $templatePosition,
                $validValues,
            ) {
                $template['rows'] = \array_map(
                    fn ($value) => static::makeCustomerOrder([
                        'id'                 => static::guidv4(),
                        'attributeId'        => $entityId,
                        'attributeValue'     => $value,
                        'template'           => $templateRow,
                        'attributeTemplate'  => $templateAttribute,
                        'positionTemplate'   => $templatePosition,
                        'assortmentTemplate' => $templateAssortment
                    ]),
                    $validValues
                );

                $template["meta"]['size'] = \count($template['rows']);
                $template['meta']['href'] = (string) $request->getUri();

                return static::createJsonResponse(body: $template);
            }
        );

        return [
            [
                static::guidv4(),
                \array_map(
                    fn () => static::fakeString(),
                    \array_fill(0, 30, null)
                )
            ],
            [
                $entityId,
                $values
            ]
        ];
    }

    public static function tearDownAfterClass(): void
    {
        static::$entity = null;
    }
}
