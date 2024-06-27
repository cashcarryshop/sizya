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
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Respect\Validation\Validator as v;

/**
 * Абстрактный класс сущностей для
 * синхронизаций МойСклад.
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
    protected array $credentials;

    /**
     * Создать экземпляр сущности
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);

        v::key('credentials', v::allOf(
            v::arrayType(),
            v::anyOf(v::length(1), v::length(2))
        ))->assert($this->settings);

        $this->credentials = $settings['credentials'];
        $this->sender = new Http\MoyskladSender;
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
}
