<?php
/**
 * Абстрактный класс сущностей для
 * синхронизаций МойСклад
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Moysklad;

use CashCarryShop\Sizya\Synchronizer\HttpSynchronizerDualRole;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Respect\Validation\Validator as v;

/**
 * Абстрактный класс сущностей для
 * синхронизаций МойСклад
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
     * Данные авторизации
     *
     * @var array
     */
    public array $credentials;

    /**
     * Создать экземпляр сущности
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        v::key('credentials', v::allOf(
            v::arrayType(),
            v::anyOf(v::length(1), v::length(2))
        ))->assert($settings);

        $this->credentials = $settings['credentials'];
    }

    /**
     * Получить сборщик запросов
     *
     * @return RequestBuilder
     */
    public function builder(): RequestBuilder
    {
        return new RequestBuilder($this->credentials);
    }

    /**
     * Получить сборщик meta параметров
     *
     * @return MetaBuilder
     */
    public function meta(): MetaBuilder
    {
        return new MetaBuilder(sprintf(
            '%s://%s/%s',
            RequestBuilder::SCHEMA,
            RequestBuilder::DOMAIN,
            RequestBuilder::PATH
        ));
    }

    /**
     * Получить BufferedBody
     *
     * @param array|string|object|resource $content Контент
     *
     * @return Io\JsonBody
     */
    public function body(array|string|object $content): Io\JsonBody
    {
        return new Io\JsonBody(fopen(
            is_resource($content)
                ? $content
                : sprintf(
                    'data://text/plain,%s',
                    is_string($content) ? $content : json_encode($content)
                ), 'r'
        ));
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
                    $this->body(
                        $response->getBody()
                            ->getContents()
                    )
                )
            ), [$promise, 'reject']
        )->otherwise([$promise, 'reject']);

        return $promise;
    }
}
