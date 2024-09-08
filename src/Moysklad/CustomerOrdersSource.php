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

use CashCarryShop\Sizya\OrdersGetterInterface;
use CashCarryShop\Sizya\OrdersGetterByAdditionalInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Sizya\Exceptions\ValidationException;
use GuzzleHttp\Promise\Utils as PromiseUtils;

/**
 * Класс для работы с заказами покупателей МойСклад
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class CustomerOrdersSource extends CustomerOrders
    implements OrdersGetterInterface, OrdersGetterByAdditionalInterface
{
    /**
     * Конвертировать позицию
     *
     * @param array $position Позиция
     *
     * @return array
     */
    private function _convertPosition(array $position): array
    {
        $output = [
            'id' => $position['id'],
            'orderId' => $position['assortment']['id'],
            'type' => $position['assortment']['meta']['type'],
            'quantity' => $position['quantity'],
            'reserve' => $position['reserve'] ?? 0,
            'price' => (int) ($position['price'] / 100),
            'discount' => $position['discount'],
            'original' => $position
        ];

        $article = $position['assortment']['article'] ?? null;
        $code = $position['assortment']['code'] ?? null;

        if ($position['assortment']['meta']['type'] === 'variant') {
            if ($article || $code) {
                $output['article'] = $code ?? $article;
                return $output;
            }
        } else if ($article || $code) {
            $output['article'] = $article ?? $code;
        }

        return $output;
    }

    /**
     * Преобразовать доп. поле
     *
     * @param array $additional Доп поле
     *
     * @return array
     */
    private function _convertAdditional(array $additional): array
    {
        return [
            'id' => $additional['id'],
            'entityId' => Utils::guidFromMeta($additional['meta']),
            'name' => $additional['name'],
            'value' => $additional['value'],
            'original' => $additional
        ];
    }

    /**
     * Преобразовать заказ
     *
     * @param array $order Заказ
     *
     * @return array
     */
    private function _convertOrder(array $order): array
    {
        $output = [
            'id' => $order['id'],
            'article' => $order['name'],
            'created' => Utils::dateToUtc($order['created']),
            'status' => Utils::guidFromMeta($order['state']['meta']),
            'positions' => array_map(
                fn ($position) => $this->_convertPosition($position),
                $order['positions']['rows']
            ),
            'additional' => array_map(
                fn ($additional) => $this->_convertAdditional($additional),
                $order['attributes'] ?? []
            ),
            'original' => $order
        ];

        if (isset($order['deliveryPlannedMoment'])) {
            $output['shipment_date'] = Utils::dateToUtc(
                $order['deliveryPlannedMoment']
            );
        }

        return $output;
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
        if (is_null($value)) {
            return false;
        }

        $builder->filter($key, $this->meta()->$key($value));
        return true;
    }

    /**
     * Получить заказы
     *
     * Смотреть `OrdersGetterInterface::getOrders`.
     *
     * @return array
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
        $this->_setFilterIfExist('agent', $builder);
        $this->_setFilterIfExist('project', $builder);
        $this->_setFilterIfExist('contract', $builder);
        $this->_setFilterIfExist('salesChannel', $builder);
        $this->_setFilterIfExist('store', $builder);

        $offset = 0;
        $maxOffset = $counter = $this->getSettings('limit');

        $orders = [];

        do {
            $clone = (clone $builder)
                ->offset($offset)
                ->limit($counter > 100 ? 100 : $counter);

            $chunk = $this->decode($this->send($clone->build('GET')))->wait();
            $orders = array_merge(
                $orders, array_map(
                    fn ($order) => $this->_convertOrder($order),
                    $chunk['rows']
                )
            );

            $offset += 100;
            $counter -= 100;
        } while (count($chunk['rows']) === 100 && $offset < $maxOffset);

        return $orders;
    }

    /**
     * Получить заказы по фильтру
     *
     * @param string $filter Фильтр
     * @param array  $values Значения для фильтра
     * @param int    $size   Размер чанка
     *
     * @return array
     */
    private function _getByFilter(string $filter, array $values, int $size = 80): array
    {
        $builder = $this->builder()
            ->point('entity/customerorder')
            ->limit(100)
            ->expand('positions.assortment');

        $promises = [];
        foreach (array_chunk($values, $size) as $chunk) {
            $clone = clone $builder;

            foreach ($chunk as $value) {
                $clone->filter($filter, $value);
            }

            $promises[] = $this->decode($this->send($clone->build('GET')));
        }

        return array_map(
            fn ($order) => $this->_convertOrder($order),
            array_merge(
                ...array_map(
                    static fn ($result) => $result['rows'],
                    PromiseUtils::all($promises)->wait()
                )
            )
        );
    }

    /**
     * Получить заказы по идентификаторам
     *
     * Должен возвращать массив с данными заказа,
     * смотреть `OrdersGetterInterface::getOrdersByIds`.
     *
     * @param array<string> $orderIds Идентификаторы заказов
     *
     * @return array
     */
    public function getOrdersByIds(array $orderIds): array
    {
        return $this->_getByFilter('id', $orderIds);
    }

    /**
     * Получить заказ по идентификатору
     *
     * Должен возвращать массив с данными заказа,
     * смотреть `OrdersGetterInterface::getOrderById`.
     *
     * @param string $orderId Идентификатор заказа
     *
     * @return array
     */
    public function getOrderById(string $orderId): array
    {
        return $this->getOrdersByIds([$orderId])[0] ?? [];
    }

    /**
     * Получить заказы по доп. полю
     *
     * Должен вовращать тот-же формат данных
     * что и `OrdersGetterByAdditionalInterface::getOrdersByAdditional`
     *
     * @param string $entityId Идентификатор сущности
     * @param array  $values   Значения доп. поля
     *
     * @return array
     */
    public function getOrdersByAdditional(string $entityId, array $values): array
    {
        return $this->_getByFilter(
            $this->meta()->href(
                "entity/customerorder/metadata/attributes/$entityId",
            ),
            $values
        );
    }
}
