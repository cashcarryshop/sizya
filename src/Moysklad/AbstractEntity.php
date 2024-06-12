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

use CashCarryShop\Synchronizer\SynchronizerSourceInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Sizya\Synchronizer\SenderDriven;
use CashCarryShop\Sizya\Http\InteractsWithDeferred;
use CashCarryShop\Sizya\Http\Utils;
use Psr\Http\Message\RequestInterface;
use React\Http\Io\ReadableBodyStream;
use React\Promise\PromiseInterface;
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
abstract class AbstractEntity extends SenderDriven
    implements SynchronizerSourceInterface, SynchronizerTargetInterface
{
    use InteractsWithDeferred;

    /**
     * Данные авторизации
     *
     * @var array
     */
    public readonly array $credentials;

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
     * @param array|string|object $content Контент
     *
     * @return Io\JsonBody
     */
    public function body(array|string|object $content): Io\JsonBody
    {
        return new Io\JsonBody(
            is_string($content)
                ? $content
                : json_encode($content)
        );
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
                        $response->withBody(
                            $this->body($buffer)
                        )
                    ),
                    fn ($reason) => $deferred->reject($reason)
                );
            },
            fn ($reason) => $deferred->reject($reason)
        );

        return $deferred->promise();
    }
}
