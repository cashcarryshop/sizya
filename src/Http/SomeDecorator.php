<?php
/**
 * Интерфейс отправителя запросов
 *
 * PHP version 8
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Http;

use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\Promise;

/**
 * Интерфейс отправителя запросов
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class SomeDecorator
{
    public SerializablePromise $promise;

    /**
     * Какой-то декоратор
     */
    public function __construct(
        public RequestInterface $request
    )
    {
        $this->promise = new SerializablePromise(new Promise);
    }
}
