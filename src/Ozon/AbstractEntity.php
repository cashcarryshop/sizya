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
use CashCarryShop\Sizya\Http\PoolInterface;
use CashCarryShop\Sizya\Http\RateLimit;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Respect\Validation\Validator as v;
use Closure;

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
abstract class AbstractEntity extends HttpSynchronizerDualRole
{
    /**
     * Идентификатор клиента
     *
     * @var int
     */
    protected int $clientId;

    /**
     * Pool-ы для Ozon
     *
     * @var array<Closure|PoolInterface>
     */
    private array $_pools = [];

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
     *
     * @return void
     */
    final protected function initialize(array $settings): void
    {
        v::keySet(
            v::key('token', v::stringType()),
            v::key('clientId', v::intType())
        )->assert($settings);

        $this->token = $settings['token'];
        $this->clientId = $settings['clientId'];

        $this->_pools['stocks'] = fn () => $this->createPool([
            'concurrency' => 5,
            'rate' => RateLimit::perMinute(80)
        ]);
    }

    /**
     * Иницилизировать сущность
     *
     * @param array $settings Настройки
     *
     * @return void
     */
    protected function init(array $settings): void
    {
        // ...
    }

    /**
     * Получить необходимый Pool
     *
     * @param string $name Название Pool
     *
     * @return PoolInterface
     */
    public function getPool(string $name): PoolInterface
    {
        $pool = $this->_pools[$name];

        if ($pool instanceof Closure) {
            return $this->_pools[$name] = $pool();
        }

        return $pool;
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
