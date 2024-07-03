<?php
/**
 * Абстрактный класс сущностей для
 * синхронизаций МойСклад.
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Moysklad;

use CashCarryShop\Sizya\Synchronizer\HttpSynchronizerDualRole;
use CashCarryShop\Sizya\Http\RateLimit;
use CashCarryShop\Sizya\Http\PoolInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Respect\Validation\Validator as v;
use Closure;

/**
 * Абстрактный класс сущностей для
 * синхронизаций МойСклад.
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class AbstractEntity extends HttpSynchronizerDualRole
{
    /**
     * Pool для МойСклад
     *
     * @var Closure|PoolInterface
     */
    private Closure|PoolInterface $_pool;

    /**
     * Данные авторизации
     *
     * @var array
     */
    protected array $credentials;

    /**
     * Иницилизировать объект
     *
     * @param array $settings Настройки
     *
     * @return void
     */
    final protected function initialize(array $settings): void
    {
        v::key('credentials', v::allOf(
            v::arrayType(),
            v::anyOf(v::length(1), v::length(2))
        ))->assert($settings);

        $this->credentials = $settings['credentials'];

        $this->_pool = fn () => $this->createPool([
            'concurrency' => 5,
            'rate' => new RateLimit(45, 3)
        ]);
        $this->init($settings);
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
     * Получить данные авторизации
     *
     * @return array
     */
    final public function getCredentials(): array
    {
        return $this->credentials;
    }

    /**
     * Получить сборщик запросов
     *
     * @return RequestBuilder
     */
    final public function builder(): RequestBuilder
    {
        return new RequestBuilder($this->credentials);
    }

    /**
     * Получить сборщик meta параметров
     *
     * @return MetaBuilder
     */
    final public function meta(): MetaBuilder
    {
        return new MetaBuilder(sprintf(
            '%s://%s/%s',
            RequestBuilder::SCHEMA,
            RequestBuilder::DOMAIN,
            RequestBuilder::PATH
        ));
    }

    /**
     * Получаем необходимый Pool
     *
     * @return PoolInterface
     */
    public function pool(): PoolInterface
    {
        if ($this->_pool instanceof Closure) {
            return $this->_pool = ($this->_pool)();
        }

        return $this->_pool;
    }
}
