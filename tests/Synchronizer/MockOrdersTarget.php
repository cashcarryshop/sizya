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

        $errors = \array_merge($firstStepErrors, $errors);

        $items = [];

        foreach ($orders as $order) {
            foreach ($order->positions as $position) {
                $found = false;
                foreach ($this->settings['products'] as $product) {
                    if ($product->id === $position->productId) {
                        $found = true;
                        break;
                    }

                    if ($product->article === $position->article) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $items[] = ByErrorDTO::fromArray([
                        'type'  => ByErrorDTO::NOT_FOUND,
                        'value' => $order
                    ]);
                    continue 2;
                }
            }

            $data       = $order->toArray();
            $data['id'] = static::guidv4();

            $items[] = $this->settings['items'][] = OrderDTO::fromArray($data);                }

        return \array_merge($items, $errors);
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

        $errors = \array_merge($firstStepErrors, $errors);

        $byIds = [];
        $byArticles = [];

        foreach ($validated as $item) {
            if ($item->id) {
                $byIds[] = $item;
                continue;
            }

            $byArticles[] = $item;
        }

        $items = [];
        \array_multisort(
            \array_column($this->settings['items'], 'article'),
            SORT_STRING,
            $this->settings['items']
        );

        \array_multisort(
            \array_column($byArticles, 'article'),
            SORT_STRING,
            $byArticles
        );

        \reset($this->settings['items']);
        foreach ($byArticles as $order) {
            if ($order->article === \current($this->settings['items'])?->article) {
                $data = \array_replace(
                    \current($this->settings['items'])->toArray(),
                    $order->toArray()
                );

                $items[] = $this->settings['items'][
                    \key($this->settings['items'])
                ] = OrderDTO::fromArray($data);
                continue;
            }

            $items[] = ByErrorDTO::fromArray([
                'type'  => ByErrorDTO::NOT_FOUND,
                'value' => $order
            ]);
        }

        \array_multisort(
            \array_column($this->settings['items'], 'id'),
            SORT_STRING,
            $this->settings['items']
        );
        \array_multisort(
            \array_column($byIds, 'id'),
            SORT_STRING,
            $byIds
        );

        \reset($this->settings['items']);
        foreach ($byIds as $order) {
            if ($order->id === \current($this->settings['items'])?->id) {
                $data = \array_replace(
                    \current($this->settings['items'])->toArray(),
                    $order->toArray()
                );

                $items[] = $this->settings['items'][
                    \key($this->settings['items'])
                ] = OrderDTO::fromArray($data);
                continue;
            }

            $items[] = ByErrorDTO::fromArray([
                'type'  => ByErrorDTO::NOT_FOUND,
                'value' => $order
            ]);
        }

        return \array_merge($items, $errors);
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
