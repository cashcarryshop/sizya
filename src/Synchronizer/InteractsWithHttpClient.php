<?php
/**
 * Этот файл является частью пакета sizya
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

use Symfony\Component\Validator\Constraints as Assert;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;

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
        if (!isset($settings['client']) || is_null($settings['client'])) {
            $settings['client'] = new Client;
        }
        $this->_settingsConstruct($settings);
    }

    /**
     * Правила валидации настроек для работы с http
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'client' => new Assert\Type(ClientInterface::class)
        ];
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
