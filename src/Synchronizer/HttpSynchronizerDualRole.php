<?php
/**
 * Элемент синхронизации, взаимодействующий с протоколом Http
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

use CashCarryShop\Synchronizer\SynchronizerDualRoleInterface;
use CashCarryShop\Sizya\Http\InteractsWithPromise;
use CashCarryShop\Sizya\Http\SenderInterface;
use CashCarryShop\Sizya\Http\Sender;

/**
 * Элемент синхронизации, взаимодействующий с протоколом Http
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class HttpSynchronizerDualRole implements SynchronizerDualRoleInterface
{
    use InteractsWithPromise;

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
    final public function withSender(SenderInterface $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * Получить отправитель запросов
     *
     * @return SenderInterface
     */
    final public function getSender(): SenderInterface
    {
        return $this->sender ??= new Sender;
    }
}
