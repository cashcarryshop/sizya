<?php
/**
 * Класс для работы с заказами покупателей МойСклад
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
use CashCarryShop\Sizya\OrdersCreatorInterface;
use CashCarryShop\Sizya\OrdersUpdaterInterface;
use GuzzleHttp\Promise\Utils as PromiseUtils;
use Respect\Validation\Validator as v;

/**
 * Класс для работы с заказами покупателей МойСклад
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class CustomerOrders extends AbstractEntity implements
    OrdersGetterInterface,
    OrdersGetterByAdditionalInterface,
    OrdersCreatorInterface,
    OrdersUpdaterInterface
{
    /**
     * Объект для работы товарами МойСклад
     *
     * @var Products
     */
    public readonly Products $products;

    /**
     * Создать экземпляр класса для
     * работы с заказами покупателей
     * МойСклад
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        $defaults = [
            'limit' => 100,
            'order' => [['created', 'desc']]
        ];

        parent::__construct(array_replace($defaults, $settings));
        v::allOf(
            v::key('products', v::instance(Products::class), false),
            v::key('limit', v::intType()->min(100), false),
            v::key('order', v::each(
                v::key(0, v::stringType()->in([
                    'created',
                    'deliveryPlannedMoment',
                    'name',
                    'id',
                    'deleted',
                    'sum'
                ])),
                v::key(1, v::stringType()->in('asc', 'desc'))
            ), false)
        )->assert($this->settings);

        $this->products = $this->getSettings('products', new Products([
            'credentials' => $this->getCredentials(),
            'client' => $this->getSettings('client')
        ]));;
    }

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
        }

        if ($article || $code) {
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

    /**
     * Установить значение если существует
     *
     * @param string $key       Ключ
     * @param array  $order     Заказ
     * @param array  $data      Данные
     * @param string $targetKey Целевой ключ
     *
     * @return bool Было ли значение установлено
     */
    private function _setIfExist(
        string $key,
        array $order,
        array &$data,
        string $targetKey = null
    ): bool {
        $targetKey ??= $key;

        if (isset($order[$key])) {
            $data[$key] = $order[$targetKey];
            return true;
        }

        return false;
    }

    /**
     * Конвертировать доп. поле для создания/обновления
     *
     * @param array $additional Доп поле
     *
     * @return array
     */
    private function _convertAdditionalPost(array $additional): array
    {
        return [
            'meta' => $this->meta()->create(
                "entity/customerorder/metadata/attributes/{$additional['entityId']}",
                'attributemetadata'
            ),
            'name' => $additional['name'],
            'value' => $additional['value']
        ];
    }

    /**
     * Конвертировать позицию для создания/обновления
     *
     * @param array $position Позиция
     *
     * @return array
     */
    private function _convertPositionPost(array $position): array
    {
        $output = [];

        $this->_setIfExist('id', $position, $output);
        $this->_setIfExist('quantity', $position, $output);
        $this->_setIfExist('discount', $position, $output);
        $this->_setIfExist('price', $position, $output)
            && $output['price'] = $output['price'] * 100;

        if (isset($position['orderId'])) {
            if (is_null($position['orderId'])) {
                $output['assortment'] = null;
            } else {
                $type = 'product';
                if (isset($position['type'])) {
                    $type = $position['type'];
                }

                $output['assortment'] = [
                    'meta' => $this->meta()->$type($position['orderId'])
                ];
            }
        }

        return $output;
    }

    /**
     * Конвертировать заказ для создания
     *
     * @param array $order Заказ
     *
     * @return array
     */
    private function _convertOrderPost(array $order): array
    {
        $output = [];

        $this->_setIfExist('id', $order, $output);
        $this->_setIfExist('article', $order, $output, 'name');
        $this->_setIfExists('description', $order, $output);
        $this->_setIfExist('created', $order, $output)
            && $output['created'] = Utils::dateToMoysklad($output['created']);

        $this->_setIfExist('status', $order, $output, 'state')
            && $output['state'] = [
                'meta' => $this->meta()->create(
                    "entity/customerorder/metadata/states/{$output['state']}",
                    'state'
                )
            ];

        $this->_setIfExist('shipment_date', $order, $output, 'deliveryPlannedMoment')
            && $output['deliveryPlannedMoment'] = Utils::dateToMoysklad(
                $output['deliveryPlannedMoment']
            );

        $this->_setIfExist('additional', $order, $output, 'attributes')
            && $output['attributes'] = array_map(
                fn ($additional) => $this->_convertAdditionalPost($additional),
                $output['attributes']
            );

        $this->_setIfExist('positions', $order, $output)
            && $output['positions'] = array_map(
                fn ($position) => $this->_convertPositionPost($position),
                $output['positions']
            );

        return $output;
    }

    /**
     * Создать или обновить заказы
     *
     * @param array $orders Заказы
     *
     * @return array<array>
     */
    private function _createOrUpdate(array $orders): array
    {
        // Собираем артикулы товаров для их получения
        $articles = [];
        foreach ($orders as $oIdx => $order) {
            foreach ($order['positions'] ?? [] as $pIdx => $position) {
                if (isset($position['orderId'])) {
                    continue;
                }

                $articles[] = [
                    'oIdx' => $oIdx,
                    'pIdx' => $pIdx,
                    'article' => $position['article']
                ];
            }
        }

        // Если есть товары, которые нужно найти по артикулам,
        // получаем их, а при отсутствии этого товара отмечаем
        // заказ в целом как "не выполнено"
        $notFoundPositions = [];
        if ($articles) {
            $products = $this->products->getByArticles(
                array_unique(
                    array_column($articles, 'article')
                )
            );

            foreach ($articles as $item) {
                foreach ($products as $product) {
                    if ($product['article'] === $item['article']) {
                        $positions = &$orders[$item['oIdx']]['positions'];
                        $positions[$item['pIdx']]['orderId'] = $product['id'];
                        $positions[$item['pIdx']]['type'] = $product['meta']['type'];
                        continue 2;
                    }
                }

                $notFoundPositions[] = $item['oIdx'];
                unset($orders[$item['oIdx']]);
            }

        }
        unset($articles);

        $builder = $this->builder()
            ->point('entity/customerorder')
            ->expand('positions.assortment');

        $promises = [];
        $chunks = array_chunk($orders, 100);
        foreach ($chunks as $chunk) {
            $promises[] = $this->decode(
                $this->send(
                    (clone $builder)
                        ->body(
                            array_map(
                                fn ($order) => $this->_convertOrderPost($order),
                                $chunk
                            )
                        )->build('POST')
                )
            );
        }

        $output = [];
        foreach (PromiseUtils::settle($promises)->wait() as $index => $result) {
            if ($result['state'] === PromiseInterface::REJECTED) {
                $chunk = $chunks[$index];
                while (current($chunk)) {
                    $output[] = [
                        'error' => true,
                        'reason' => $result['reason']->getMessage()
                    ];

                    next($chunk);
                }
                continue;
            }

            foreach ($result['value'] as $idx => $order) {
                if (in_array(($index * 100) + $idx, $notFoundPositions)) {
                    $output[] = [
                        'error' => true,
                        'reason' => 'Product not found',
                        'original' => null
                    ];
                }

                $order = $this->_convertOrder($order);
                $order['error'] = false;
                $output[] = $order;
            }
        }

        return $output;
    }


    /**
     * Создать переданные заказы
     *
     * Смотреть `OrdersCreatorInterface::massCreateOrders`
     *
     * @param array $orders Заказы
     *
     * @return array<array>
     */
    public function massCreateOrders(array $orders): array
    {
        return $this->_createOrUpdate($orders);
    }

    /**
     * Создать заказ
     *
     * Смотреть `OrdersCreatorInterface::createOrder`
     *
     * @param array $order Заказ
     *
     * @return array
     */
    public function createOrder(array $order): array
    {
        return $this->_createOrUpdate([$order])[0];
    }

    /**
     * Обновить заказы
     *
     * Смотреть `OrdersUpdaterInterface::massUpdateOrders`
     *
     * @param array $orders Заказы
     *
     * @return array<array>
     */
    public function massUpdateOrders(array $orders): array
    {
        return $this->_createOrUpdate($orders);
    }

    /**
     * Обновить заказ
     *
     * Смотреть `OrdersUpdaterInterface::updateOrder`
     *
     * @param array $order Заказ
     *
     * @return array
     */
    public function updateOrder(array $order): array
    {
        return $this->_createOrUpdate([$order])[0];
    }
}
