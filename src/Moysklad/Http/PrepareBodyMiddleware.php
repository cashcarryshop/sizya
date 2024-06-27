<?php
/**
 * Класс обработчика тела ответа
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

use CashCarryShop\Sizya\Http\Utils;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Класс обработчика тела ответа
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class PrepareBodyMiddleware
{
    /**
     * Создать обработчик
     *
     * @return callable
     */
    public static function create(): callable
    {
        return static function (callable $handler): PrepareBodyMiddleware {
            return new PrepareBodyMiddleware($handler);
        };
    }

    /**
     * Следующий обработчик
     *
     * Обработчик, который возвращает Promise
     *
     * @var callable
     */
    private $_nextHanlder;

    /**
     * Создкть экземпляр обработчика
     *
     * @param callable $nextHandler Обработчик
     */
    public function __construct(callable $nextHandler)
    {
        $this->_nextHandler = $nextHandler;
    }

    /**
     * Вызвать обработчик
     *
     * @param RequestInterface $request Запрос
     * @param array            $options Опции
     *
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        return ($this->_nextHandler)($request, $options)->then(
            static function (ResponseInterface $response) {
                if ($response->getHeader('Content-Encoding')[0] ?? null === 'gzip') {
                    if ($decoded = @gzdeocde($response->getBody()->getContents())) {
                        return $response->withBody(Utils::getJsonStream($decoded));
                    }
                }

                if (method_exists($response->getBody(), 'toArray')) {
                    return $response;
                }

                return $response->withBody(
                    Utils::getJsonStream(
                        $response->getBody()
                    )
                );
            }
        );
    }
}
