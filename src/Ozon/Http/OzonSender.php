<?php
/**
 * Класс отправителя для Ozon
 *
 * PHP version 8
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Ozon\Http;

use CashCarryShop\Sizya\Http\Sender;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

/**
 * Класс отправителя для Ozon
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class OzonSender extends Sender
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
