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

use CashCarryShop\Sizya\OrdersGetterInterface;
use CashCarryShop\Sizya\OrdersGetterByAdditionalInterface;
use CashCarryShop\Sizya\OrdersGetterByExternalCodesInterface;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\PositionDTO;
use CashCarryShop\Sizya\DTO\AdditionalDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\Utils as SizyaUtils;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Класс для работы с заказами покупателей МойСклад.
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class CustomerOrdersSource extends CustomerOrders
    implements OrdersGetterInterface,
               OrdersGetterByAdditionalInterface,
               OrdersGetterByExternalCodesInterface
{
    /**
     * Получить заказы
     *
     * @see OrdersGetterInterface
     *
     * @return OrderDTO[]
     */
    public function getOrders(): array
    {
        $builder = $this->builder()
            ->point('entity/customerorder')
            ->expand('positions.assortment');

        foreach ($this->getSettings('order') as $order) {
            $builder->order(...$order);
        }

        $this->_setFilterIfExist('organization', $builder);
        $this->_setFilterIfExist('agent',        $builder);
        $this->_setFilterIfExist('project',      $builder);
        $this->_setFilterIfExist('contract',     $builder);
        $this->_setFilterIfExist('salesChannel', $builder);
        $this->_setFilterIfExist('store',        $builder);

        return Utils::getAll(
            $builder,
            $this->getSettings('limit'),
            \min($this->getSettings('limit'), 100),
            [$this, 'send'],
            fn ($response) => \array_map(
                fn ($order) => $this->_convertOrder($order),
                $this->decodeResponse($response)['rows']
            )
        );
    }

    /**
     * Получить заказы по идентификаторам
     *
     * @see OrdersGetterInterface
     *
     * @param array<string> $ordersIds Идентификаторы заказов
     *
     * @return array<int, OrderDTO|ByErrorDTO>
     */
    public function getOrdersByIds(array $ordersIds): array
    {
        [
            $validated,
            $errors
        ] = SizyaUtils::splitByValidationErrors(
            $this->getValidator(), $ordersIds, [
                new Assert\Type('string'),
                new Assert\NotBlank,
                new Assert\Uuid(strict: false)
            ]
        );
        unset($ordersIds);

        return \array_merge(
            $this->_getByFilter(
                'id',
                $validated,
                static fn ($order) => $order->id
            ),
            $errors
        );
    }

    /**
     * Получить заказ по идентификатору
     *
     * @see OrdersGetterInterface
     *
     * @param string $orderId Идентификатор заказа
     *
     * @return OrderDTO|ByErrorDTO
     */
    public function getOrderById(string $orderId): OrderDTO|ByErrorDTO
    {
        return $this->getOrdersByIds([$orderId])[0] ?? [];
    }

    /**
     * Получить заказы по доп. полю
     *
     * @see OrdersGetterByAdditionalInterface
     *
     * @param string $entityId Идентификатор сущности
     * @param array  $values   Значения доп. поля
     *
     * @return array<int, OrderDTO|ByErrorDTO>
     */
    public function getOrdersByAdditional(string $entityId, array $values): array
    {
        return $this->_getByFilter(
            $this->meta()->href("entity/customerorder/metadata/attributes/$entityId"),
            $values,
            static function ($order) use ($entityId) {
                foreach ($order->additionals as $key => $additional) {
                    if ($additional->entityId === $entityId) {
                        return $additional->value;
                    }
                }
            }
        );
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
        [
            $validated,
            $errors
        ] = SizyaUtils::splitByValidationErrors(
            $this->getValidator(), $codes, [
                new Assert\Type('string'),
                new Assert\NotBlank
            ]
        );

        return \array_merge(
            $this->_getByFilter(
                'externalCode',
                $validated,
                static fn ($order) => $order->externalCode
            ),
            $errors
        );
    }

    /**
     * Получить заказы по фильтру
     *
     * @see Utils
     *
     * @param string   $filter Фильтр
     * @param array    $values Значения для фильтра
     * @param callable $pluck  Функция для того, чтобы вытащить
     *                         значение из dto по которому
     *                         была фильтровка
     *
     *
     * @return array<int, OrderDTO|ByErrorDTO>
     */
    private function _getByFilter(
        string   $filter,
        array    $values,
        callable $pluck
    ): array {
        return Utils::getByFilter(
            $filter,
            $values,
            $this->builder()
                ->point('entity/customerorder')
                ->limit(100)
                ->expand('positions.assortment'),
            [$this, 'send'],
            function ($response) use ($pluck) {
                $dtos   = [];
                $values = [];

                foreach ($this->decodeResponse($response)['rows'] as $item) {
                    $dtos[]   = $dto = $this->_convertOrder($item);
                    $values[] = $pluck($dto);
                }

                return [
                    'dtos'   => $dtos,
                    'values' => $values
                ];
            },
            100
        )->wait();
    }

    /**
     * Установить фильтр meta если есть настройка
     *
     * @param string $key     Ключ
     * @param object $builder Строитель запросов
     *
     * @return bool
     */
    private function _setFilterIfExist(string $key, object &$builder): bool
    {
        $value = $this->getSettings($key);
        if ($value === null) {
            return false;
        }

        $builder->filter($key, $this->meta()->$key($value));
        return true;
    }

    /**
     * Преобразовать заказ
     *
     * @param array $order Заказ
     *
     * @return OrderDTO
     */
    private function _convertOrder(array $order): OrderDTO
    {
        $data = [
            'id'        => $order['id'],
            'created'   => Utils::dateToUtc($order['created']),
            'status'    => Utils::guidFromMeta($order['state']['meta']),
            'externalCode' => $order['externalCode'],
            'positions' => \array_map(
                fn ($position) => $this->convertPositionToDto($position),
                $order['positions']['rows']
            ),
            'additionals' => \array_map(
                fn ($attribute) => $this->convertAttributeToDto($attribute),
                $order['attributes'] ?? []
            ),
            'original' => $order,
            'description' => $order['description'] ?? null
        ];

        if (isset($order['deliveryPlannedMoment'])
            && $order['deliveryPlannedMoment']
        ) {
            $data['shipmentDate'] = Utils::dateToUtc(
                $order['deliveryPlannedMoment']
            );
        }

        return OrderDTO::fromArray($data);
    }

    /**
     * Конвертировать позицию заказа покупателя
     *
     * @param array $position Данные позиции
     *
     * @return PositionDTO
     */
    protected function convertPositionToDto(array $position): PositionDTO
    {
        $data = [
            'id'        => $position['id'],
            'productId' => $position['assortment']['id'],
            'type'      => $position['assortment']['meta']['type'],
            'quantity'  => (int) $position['quantity'],
            'reserve'   => (int) ($position['reserve'] ?? 0),
            'price'     => (float) ($position['price'] / 100),
            'discount'  => (float) $position['discount'],
            'vat'       => $position['vat'],
            'original'  => $position
        ];

        $article = $position['assortment']['article'] ?? null;
        $code    = $position['assortment']['code']    ?? null;

        if ($position['assortment']['meta']['type'] === 'variant') {
            if ($article || $code) {
                $data['article'] = (string) ($code ?? $article);
            } else {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot get assortment article for assortment id [%s]',
                        $position['assortment']['id']
                    )
                );
            }
        } else if ($article || $code) {
            $data['article'] = $article ?? $code;
        }

        return PositionDTO::fromArray($data);
    }

    /**
     * Преобразовать дополнительное поле
     * заказа покупателя
     *
     * @param array $attribute Данные дополнительного поля
     *
     * @return AdditionalDTO
     */
    protected function convertAttributeToDto(array $attribute): AdditionalDTO
    {
        return AdditionalDTO::fromArray([
            'id'        => $attribute['id'],
            'entityId'  => $attribute['id'],
            'name'      => $attribute['name'],
            'value'     => $attribute['value'],
            'original'  => $attribute
        ]);
    }
}
