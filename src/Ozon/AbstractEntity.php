<?php
/**
 * Абстрактный класс сущностей для
 * синхронизаций Ozon
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Ozon;

use CashCarryShop\Sizya\Synchronizer\HttpSynchronizerDualRole;
use CashCarryShop\Sizya\Http\Utils;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Respect\Validation\Validator as v;

/**
 * Абстрактный класс сущностей для
 * синхронизаций Ozon
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class AbstractEntity extends HttpSynchronizerDualRole
{
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
     * Все настройки
     *
     * @var array
     */
    protected array $settings;

    /**
     * Создать экземпляр сущности
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        v::keySet(
            v::key('token', v::stringType()),
            v::key('clientId', v::intType())
        )->assert($settings);

        $this->token = $settings['token'];
        $this->clientId = $settings['clientId'];
        $this->settings = $settings;
    }

    /**
     * Получить данные авторизации
     *
     * ```php
     * [
     *     'clientId' => $this->clientId,
     *     'token'    => $this->token
     * ]
     * ```
     *
     * @return array
     */
    public function getCredentials(): array
    {
        return [
            'clientId' => $this->clientId,
            'token' => $this->token
        ];
    }

    /**
     * Получить настройки
     *
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Получить сборщик запросов
     *
     * @return RequestBuilder
     */
    public function builder(): RequestBuilder
    {
        return new RequestBuilder($this->token, $this->clientId);
    }

    /**
     * Отправить запрос
     *
     * @param RequestInterface $request Запрос
     *
     * @return PromiseInterface
     */
    public function send(RequestInterface $request): PromiseInterface
    {
        $promise = $this->promise();

        $this->getSender()->sendRequest($request)->then(
            fn ($response) => $promise->resolve(
                $response->withBody(
                    Utils::getJsonBody(
                        $response->getBody()
                            ->getContents()
                    )
                )
            ),
            [$promise, 'reject']
        );

        return $promise;
    }
}
