<?php
/**
 * Абстрактный класс сущностей для
 * синхронизаций Ozon.
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
 * синхронизаций Ozon.
 *
 * Обязательно, чтобы, если вы передаете
 * свой Sender в классы, наследующий этот,
 * в теле ответа (Response) возвращался JsonStream, или
 * любой другой поток, который имел метод toArray
 * и мог конвертироваться в массив.
 *
 * По умолчанию устанавливается нативный Sender,
 * использующий Http\PrepareBodyMiddleware для
 * обработки ответов от сервера.
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
     * Создать экземпляр сущности
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);

        v::keySet(
            v::key('token', v::stringType()),
            v::key('clientId', v::intType())
        )->assert($settings);

        $this->token = $settings['token'];
        $this->clientId = $settings['clientId'];
        $this->sender = new Http\OzonSender;
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
        return new RequestBuilder($this->token, $this->clientId);
    }
}
