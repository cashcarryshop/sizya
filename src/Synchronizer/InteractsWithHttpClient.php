<?php
/**
 * Трейт с методами для работы с клиентом GuzzleHttp
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

use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use InvalidArgumentException;

/**
 * Трейт с методами для работы с клиентом GuzzleHttp
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait InteractsWithHttpClient
{
    use InteractsWithSettings {
        __construct as private _settingsConstruct;
    }

    /**
     * Создать эезмпляр элемента синхронизации,
     * который может взаимодействовать
     * Http клиентом
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        if (isset($settings['client'])) {
            if (is_a($settings['client'], ClientInterface::class)) {
                $this->_settingsConstruct($settings);
                return;
            }

            throw new InvalidArgumentException(
                'Setting [client] must be implements ' . ClientInterface::class
            );
        }

        $settings['client'] = new Client;
        $this->_settingsConstruct($settings);
    }

    /**
     * Отправить запрос
     *
     * @param RequestInterface $request Запрос
     *
     * @return PromiseInterface
     */
    final public function send(RequestInterface $request): PromiseInterface
    {
        return $this->getSettings('client')->sendAsync($request);
    }
}
