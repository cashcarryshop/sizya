<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya
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
use Symfony\Component\Validator\Constraints as Assert;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use JsonException;

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
        rules as private _httpRules;
    }

    /**
     * Иницилизировать объект
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        $this->_httpClientConstruct($settings);
    }

    /**
     * Основные правила валидации настроек
     * для работы с МойСклад
     *
     * @return array
     */
    protected function rules(): array
    {
        return array_merge(
            $this->_httpRules(), [
                'credentials' => [
                    new Assert\All([
                        new Assert\Type('string'),
                        new Assert\NotBlank
                    ]),
                ]
            ]
        );
    }

    /**
     * Получить сборщик запросов
     *
     * @return RequestBuilder
     */
    final public function builder(): RequestBuilder
    {
        return new RequestBuilder($this->getSettings('credentials'));
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
    public function decode(PromiseInterface $promise): PromiseInterface
    {
        return $promise->then([$this, 'decodeResponse']);
    }

    /**
     * Декодировать response
     *
     * @param ResponseInterface $response Ответ
     *
     * @return array
     * @throws JsonException Если произошла ошибка декодирования
     */
    public function decodeResponse(ResponseInterface $response): array
    {
        return \json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
