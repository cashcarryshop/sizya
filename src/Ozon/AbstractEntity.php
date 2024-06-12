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
use React\Promise\PromiseInterface;
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
    public readonly int $clientid;

    /**
     * Токен
     *
     * @var string
     */
    public readonly string $token;

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
        $deferred = $this->deferred();

        $this->getSender()->sendRequest($request)->then(
            function ($response) use ($deferred) {
                Utils::waitFill($this->deferred(), $response->getBody())->then(
                    fn ($buffer) =>  $deferred->resolve(
                        $response->withBody(Utils::getJsonBody($buffer))
                    ),
                    fn ($reason) => $deferred->reject($reason)
                );
            },
            fn ($reason) => $deferred->reject($reason)
        );

        return $deferred->promise();
    }
}
