<?php
/**
 * Трейт с методами для взаимодействия с
 * МойСклад API
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

use CashCarryShop\Sizya\Synchronizer\InteractsWithHttpClient;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Respect\Validation\Validator as v;

/**
 * Трейт с методами для взаимодействия с
 * МойСклад API
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait InteractsWithMoysklad
{
    use InteractsWithHttpClient {
        __construct as private _httpClientConstruct;
    }

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
     */
    public function __construct(array $settings)
    {
        $this->_httpClientConstruct($settings);

        v::key('credentials', v::allOf(
            v::arrayType(),
            v::anyOf(v::length(1), v::length(2))
        ))->assert($settings);

        $this->credentials = $settings['credentials'];
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
