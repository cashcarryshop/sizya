<?php
/**
 * Класс отправителя для МойСклад
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Moysklad\Http;

use CashCarryShop\Sizya\Http\Sender;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

/**
 * Класс отправителя для МойСклад
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class MoyskladSender extends Sender
{
    /**
     * Создание экземпляра отправителя
     *
     * @param ?ClientInterface $client Клиент
     */
    public function __construct(?ClientInterface $client = null)
    {
        if (null === $client) {
            $stack = HandlerStack::create();
            $stack->push(PrepareBodyMiddleware::create());
            $client = new Client(['handler' => $stack]);
        }

        parent::__construct($client);
    }
}
