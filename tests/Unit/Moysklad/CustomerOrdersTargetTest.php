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

use CashCarryShop\Sizya\OrdersCreatorInterface;
use CashCarryShop\Sizya\OrdersUpdaterInterface;
use CashCarryShop\Sizya\Moysklad\CustomerOrdersTarget;
use CashCarryShop\Sizya\Tests\Traits\OrdersCreatorTests;
use CashCarryShop\Sizya\Tests\Traits\OrdersUpdaterTests;
use CashCarryShop\Sizya\Tests\Traits\InteractsWithMoysklad;
use CashCarryShop\Sizya\DTO\OrderCreateDTO;
use CashCarryShop\Sizya\DTO\OrderUpdateDTO;
use CashCarryShop\Sizya\DTO\AdditionalCreateDTO;
use CashCarryShop\Sizya\DTO\AdditionalUpdateDTO;
use CashCarryShop\Sizya\DTO\PositionCreateDTO;
use CashCarryShop\Sizya\DTO\PositionUpdateDTO;
use CashCarryShop\Sizya\DTO\DTOInterface;
use CashCarryShop\Sizya\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Тесты класса для создания/обновления заказов МойСклад.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(CustomerOrdersTarget::class)]
class CustomerOrdersTargetTest extends TestCase
{
    use OrdersCreatorTests;
    use OrdersUpdaterTests;
    use InteractsWithMoysklad;

    /**
     * Сущность.
     *
     * @var CustomerOrdersTarget
     */
    protected static CustomerOrdersTarget $entity;

    /**
     * Guid организации.
     *
     * @var string
     */
    protected static string $organization;

    /**
     * Guid контрагента.
     *
     * @var string
     */
    protected static string $agent;

    public static function setUpBeforeClass(): void
    {
        static::$entity  = new CustomerOrdersTarget([
            'credentials'  => ['login', 'password'],
            'organization' => static::$organization = static::guidv4(),
            'agent'        => static::$agent        = static::guidv4(),
            'client'       => static::createHttpClient(static::$handler),
            'limit'        => 100
        ]);
    }

    protected function createOrdersUpdater(): OrdersUpdaterInterface
    {
        return static::$entity ?? null;
    }

    protected function ordersUpdateProvider(): array
    {
        [
            'provides' => $provides
        ] = static::generateProvideData([
            'validGenerator' => fn () => $this->makeValidDto(
                OrderUpdateDTO::class,
                AdditionalUpdateDTO::class,
                PositionUpdateDTO::class
            ),
            'invalidGenerator' => fn () => $this->makeInvalidDTO(
                OrderUpdateDTO::class,
                AdditionalUpdateDTO::class,
                PositionUpdateDTO::class
            )
        ]);

        static::$handler->append(
            ...\array_fill(
                0,
                \count($provides),
                static::createMethodResponse('post@1.2/entity/customerorder')
            )
        );

        return $provides;
    }

    protected function orderUpdateProvider(): OrderUpdateDTO
    {
        static::$handler->append(
            static::createMethodResponse('post@1.2/entity/customerorder')
        );

        return $this->makeValidDto(
            OrderUpdateDTO::class,
            AdditionalUpdateDTO::class,
            PositionUpdateDTO::class
        );
    }

    protected function createOrdersCreator(): ?OrdersCreatorInterface
    {
        return static::$entity ?? null;
    }

    protected function ordersCreateProvider(): array
    {
        [
            'provides' => $provides
        ] = static::generateProvideData([
            'validGenerator' => fn () => $this->makeValidDto(
                OrderCreateDTO::class,
                AdditionalCreateDTO::class,
                PositionCreateDTO::class
            ),
            'invalidGenerator' => fn () => $this->makeInvalidDTO(
                OrderCreateDTO::class,
                AdditionalCreateDTO::class,
                PositionCreateDTO::class
            )
        ]);

        static::$handler->append(
            ...\array_fill(
                0,
                \count($provides),
                static::createMethodResponse('post@1.2/entity/customerorder')
            ),
        );

        return $provides;
    }

    protected function orderCreateProvider(): OrderCreateDTO
    {
        static::$handler->append(
            // static::createMethodResponse('1.2/entity/assortment'),
            static::createMethodResponse('post@1.2/entity/customerorder')
        );

        return $this->makeValidDto(
            OrderCreateDTO::class,
            AdditionalCreateDTO::class,
            PositionCreateDTO::class
        );
    }

    public static function tearDownAfterClass(): void
    {
        static::$entity = null;
    }

    /**
     * Создать валидный dto создания/обновления заказа.
     *
     * @param string $orderClass      Класс dto заказа
     * @param string $additionalClass Класс dto доп. поля
     * @param string $positionClass   Класс dto для позиции
     *
     * @return DTOInterface
     */
    protected function makeValidDto(
        string $orderClass,
        string $additionalClass,
        string $positionClass
    ): DTOInterface {
        $positionData = [
            'productId' => static::guidv4(),
            'article'   => static::fakeArticle(),
            'type'      => \random_int(0, 3) === 3 ? 'product' : 'variant',
            'quantity'  => \random_int(0, 15),
            'discount'  => (float) \random_int(0, 10),
            'currency'  => 'RUB',
            'vat'       => \random_int(0, 3) === 3
        ];

        $isUpdate = $orderClass === OrderUpdateDTO::class;

        if ($isUpdate) {
            $positionData['id'] = static::guidv4();
        }

        $additionalData = [
            'entityId' => static::guidv4(),
            'value'    => static::fakeString()
        ];

        if ($isUpdate) {
            $additionalData['id'] = static::guidv4();
        }

        $orderData = [
            'created'        => static::fakeDtoDate(),
            'status'         => static::guidv4(),
            'shipmentDate'   => static::fakeDtoDate(),
            'deliveringDate' => static::fakeDtoDate(),
            'description'    => static::fakeString(),
            'additionals'    => [
                $additionalClass::fromArray($additionalData)
            ]
        ];

        if ($isUpdate) {
            $orderData['id'] = static::guidv4();
        } else {
            $orderData['positions'] = [
                $positionClass::fromArray($positionData)
            ];
        }

        return $orderClass::fromArray($orderData);
    }

    /**
     * Создать не валидный dto обновления/создания заказа
     *
     * @param string $orderClass      Класс dto заказа
     * @param string $additionalClass Класс dto доп. поля
     * @param string $positionClass   Класс dto для позиции
     *
     * @return mixed
     */
    protected function makeInvalidDto(
        string $orderClass,
        string $additionalClass,
        string $positionClass
    ): mixed {
        $randInt = \random_int(0, 5);
        if ($randInt === 5) {
            return static::fakeString();
        }

        if ($randInt === 4) {
            return \random_int(-30, 30);
        }

        $positionData = [
            'productId' => static::guidv4(),
            'article'   => static::fakeArticle(),
            'type'      => \random_int(0, 3) === 3 ? 'product' : 'variant',
            'quantity'  => \random_int(0, 15),
            'discount'  => (float) \random_int(0, 10),
            'currency'  => 'RUB',
            'vat'       => \random_int(0, 3) === 3
        ];

        if ($randInt === 3) {
            return $positionData;
        }
        unset($positionData);

        $dto = $this->makeValidDto(
            $orderClass,
            $additionalClass,
            $positionClass
        );

        if ($randInt === 2) {
            $dto->id = \random_int(0, 100000);
            return $dto;
        }

        if ($randInt === 1 && $dto instanceof OrderCreateDTO) {
            $dto->positions[0]->id = \random_int(-1000, 100000);
            return $dto;
        }

        $dto->additionals[0]->entityId = \random_int(-1000, 10000);
        return $dto;
    }
}
