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

use CashCarryShop\Sizya\OrdersCreatorInterface;
use CashCarryShop\Sizya\OrdersUpdaterInterface;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\PositionDTO;
use CashCarryShop\Sizya\DTO\OrderUpdateDTO;
use CashCarryShop\Sizya\DTO\OrderCreateDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\Utils;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Тестовый класс цели синхронизации заказов.
 *
 * @category TestMock
 * @package  Sizya
 * @author   TheWhatis <anton-gnogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class MockOrdersTarget extends MockOrdersSource
    implements SynchronizerTargetInterface,
               OrdersUpdaterInterface,
               OrdersCreatorInterface
{
    /**
     * Массово создать заказы
     *
     * Количество возвращаемых элементов должно
     * соответствовать переданным.
     *
     * @param OrderCreateDTO[] $orders Заказы для создания
     *
     * @see OrderCreateDTO
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return array<int, OrderDTO|ByErrorDTO>
     */
    public function massCreateOrders(array $orders): array
    {
        [
            $firstStepValidated,
            $firstStepErrors
        ] = Utils::splitByValidationErrors(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator(),
            $orders,
            [
                new Assert\NotBlank,
                new Assert\Type(OrderCreateDTO::class)
            ]

        );
        unset($orders);

        [
            $validated,
            $errors
        ] = Utils::splitByValidationErrors(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator(),
            $firstStepValidated,
            [
                new Assert\Valid
            ]
        );
        unset($firstStepValidated);

        $items = [];
        foreach ($validated as $order) {
            $positions = [];
            foreach ($order->positions as $position) {
                foreach ($this->settings['products'] as $product) {
                    if ($product['id'] === $position->productId
                        || $product['article'] === $position->article
                    ) {
                        $posData         = $position->toArray();
                        $posData['id']   = $posData['id'] ?? static::guidv4();
                        $posData['type'] = $posData['type'] ?? 'product';
                        $positions[] = PositionDTO::fromArray($posData);

                        continue 2;
                    }
                }

                $items[] = ByErrorDTO::fromArray([
                    'type'  => ByErrorDTO::NOT_FOUND,
                    'value' => $order
                ]);
                break 2;
            }

            $data              = $order->toArray();
            $data['id']        = static::guidv4();
            $data['original']  = $data;
            $data['positions'] = $positions;

            $items[] = $this->settings['items'][] = OrderDTO::fromArray($data);                }

        return \array_merge($items, $firstStepErrors, $errors);
    }

    /**
     * Создать заказ
     *
     * @param OrderCreateDTO $order Заказ
     *
     * @see OrderCreateDTO
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return OrderDTO|ByErrorDTO
     */
    public function createOrder(OrderCreateDTO $order): OrderDTO|ByErrorDTO
    {
        return $this->massCreateOrders([$order])[0];
    }

    /**
     * Массово обновить заказы
     *
     * Количество возвращаемых элементов должно
     * соответствовать переданным.
     *
     * @param OrderUpdateDTO[] $orders Заказы
     *
     * @see OrderUpdateDTO
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return array<int, OrderDTO|ByErrorDTO>
     */
    public function massUpdateOrders(array $orders): array
    {
        if (!$orders) {
            return [];
        }

        [
            $firstStepValidated,
            $firstStepErrors
        ] = Utils::splitByValidationErrors(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator(),
            $orders,
            [
                new Assert\NotBlank,
                new Assert\Type(OrderUpdateDTO::class)
            ]

        );
        unset($orders);

        [
            $validated,
            $errors
        ] = Utils::splitByValidationErrors(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator(),
            $firstStepValidated,
            [
                new Assert\Valid
            ]
        );
        unset($firstStepValidated);

        $items = [];
        \array_multisort(
            \array_column($this->settings['items'], 'id'),
            SORT_STRING,
            $this->settings['items']
        );
        \array_multisort(
            \array_column($validated, 'id'),
            SORT_STRING,
            $validated
        );

        $countProducts = \count($this->settings['products']);

        \reset($this->settings['items']);
        foreach ($validated as $order) {
            $current = \current($this->settings['items']);

            if ($current !== false && $order->id === $current->id) {
                $data = \array_replace($current->toArray(), $order->toArray());

                $idx = \key($this->settings['items']);

                $items[] = $this->settings['items'][$idx] = OrderDTO::fromArray($data);
                \next($this->settings['items']);
                continue;
            }

            $items[] = ByErrorDTO::fromArray([
                'type'  => ByErrorDTO::NOT_FOUND,
                'value' => $order
            ]);
        }

        return \array_merge($items, $firstStepErrors, $errors);
    }

    /**
     * Обновить заказ по идентификатору
     *
     * @param OrderUpdateDTO $order Данные заказа
     *
     * @see OrderUpdateDTO
     * @see OrderDTO
     * @see ByErrorDTO
     *
     * @return OrderDTO|ByErrorDTO
     */
    public function updateOrder(OrderUpdateDTO $order): OrderDTO|ByErrorDTO
    {
        return $this->massUpdateOrders([$order])[0];
    }
}
