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

use CashCarryShop\Sizya\OrdersCreatorInterface;
use CashCarryShop\Sizya\OrdersUpdaterInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
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
class CustomerOrders extends CustomerOrdersSource
    implements SynchronizerTargetInterface,
               OrdersCreatorInterface,
               OrdersUpdaterInterface
{
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
     * Установить значение если оно имеется в настройках
     *
     * @param string $key  Ключ
     * @param array  $data Куда установить значение
     *
     * @return bool Было ли значение установлено
     */
    private function _setIfExistInSettings(string $key, array &$data): bool
    {
        if ($value = $this->getSettings($key)) {
            $data[$key] = $value;
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
    private function _convertAdditional(array $additional): array
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
    private function _convertPosition(array $position): array
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
    private function _convertOrder(array $order): array
    {
        $output = [
            'organization' => $this->getSettings('organization'),
            'agent' => $this->getSettings('agent')
        ];

        $this->_setIfExistInSettings('vatEnabled', $output);
        $this->_setIfExistInSettings('vatIncluded', $output);
        $this->_setIfExistInSettings('project', $output)
            && $output['project'] = $this->meta()->project($output['project']);
        $this->_setIfExistInSettings('contract', $output)
            && $output['contract'] = $this->meta()->contract($output['contract']);
        $this->_setIfExistInSettings('store', $output)
            && $output['store'] = $this->meta()->store($output['store']);
        $this->_setIfExistInSettings('salesChannel', $output)
            && $output['salesChannel'] = $this->meta()
            ->salesChannel($output['salesChannel']);

        $this->_setIfExist('id', $order, $output);
        $this->_setIfExist('article', $order, $output, 'name');
        $this->_setIfExist('description', $order, $output);
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
                fn ($additional) => $this->_convertAdditional($additional),
                $output['attributes']
            );

        $this->_setIfExist('positions', $order, $output)
            && $output['positions'] = array_map(
                fn ($position) => $this->_convertPosition($position),
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
                                fn ($order) => $this->_convertOrder($order),
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
