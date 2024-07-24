<?php
/**
 * Трейт с методами для взаимодействия с
 * Ozon Seller Api
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Ozon;

use CashCarryShop\Sizya\Synchronizer\InteractsWithHttpClient;
use GuzzleHttp\Promise\PromiseInterface;
use Respect\Validation\Validator as v;

/**
 * Трейт с методами для взаимодействия с
 * Ozon Seller Api
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait InteractsWithOzonSeller
{
    use InteractsWithHttpClient {
        __construct as private _clientConstruct;
    }

    /**
     * Идентификатор клиента
     *
     * @var int
     */
    protected int $clientId;

    /**
     * Токен
     *
     * @var string
     */
    protected string $token;

    /**
     * Создать экземпляр сущности
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        $this->_clientConstruct($settings);
        v::key('token', v::stringType())
            ->key('clientId', v::intType())
            ->assert($settings);

        $this->token = $settings['token'];
        $this->clientId = $settings['clientId'];
    }

    /**
     * Получить идентификатор клиента
     *
     * @return int
     */
    final public function getClientId(): int
    {
        return $this->clientId;
    }

    /**
     * Получить токен
     *
     * @return string
     */
    final public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Получить сборщик запросов
     *
     * @return RequestBuilder
     */
    final public function builder(): RequestBuilder
    {
        return new RequestBuilder($this->getToken(), $this->getClientId());
    }

    /**
     * Получить данные из ответа
     *
     * @param PromiseInterface $promise Promise в который передается Response
     *
     * @return PromiseInterface
     */
    protected function decode(PromiseInterface $promise): PromiseInterface
    {
        return $promise->then(static fn ($response) => json_decode(
            $response->getBody()->getContents(), true
        ));
    }
}
