<?php
/**
 * Интерфейс с методами для работы с SenderInterface
 *
 * PHP version 8
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Synchronizer;

use CashCarryShop\Sizya\Http\SenderInterface;

/**
 * Интерфейс с методами для работы с SenderInterface
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
interface SenderDrivenInterface
{
    /**
     * Установить отправителя запросов
     *
     * @param SenderInterface $sender Отправитель
     *
     * @return static
     */
    public function withSender(SenderInterface $sender): static;

    /**
     * Получить отправитель запросов
     *
     * @return SenderInterface
     */
    public function getSender(): SenderInterface;
}
