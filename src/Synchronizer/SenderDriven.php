<?php
/**
 * Класс со стандартной реализацией SenderInterface
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
use CashCarryShop\Sizya\Http\Sender;

/**
 * Класс со стандартной реализацией SenderInterface
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class SenderDriven implements SenderDrivenInterface
{
    /**
     * Отправитель запросов
     *
     * @var SenderInterface
     */
    protected SenderInterface $sender;

    /**
     * Установить отправителя запросов
     *
     * @param SenderInterface $sender Отправитель
     *
     * @return static
     */
    public function withSender(SenderInterface $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * Получить отправитель запросов
     *
     * @return SenderInterface
     */
    public function getSender(): SenderInterface
    {
        return $this->sender ??= Sender::create();
    }
}
