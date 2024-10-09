<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Ozon;

use CashCarryShop\Sizya\Synchronizer\InteractsWithHttpClient;
use Symfony\Component\Validator\Constraints as Assert;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use JsonException;

/**
 * Трейт с методами для взаимодействия с
 * Ozon Seller Api.
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait InteractsWithOzonSeller
{
    use InteractsWithHttpClient {
        __construct as private _httpConstruct;
        rules as private __httpRules;
    }

    /**
     * Создать экземпляр сущности
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        $this->_httpConstruct($settings);
    }

    /**
     * Правила валидации для настроек
     *
     * @return array
     */
    protected function rules(): array
    {
        return array_merge(
            $this->__httpRules(), [
                'token' => [
                    new Assert\Type('string'),
                    new Assert\NotBlank
                ],
                'clientId' => [
                    new Assert\Type('int'),
                    new Assert\PositiveOrZero
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
        return new RequestBuilder(
            $this->getSettings('token'),
            $this->getSettings('clientId')
        );
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
     * Декодировать ResponseInterface
     *
     * @param ResponseInterface $response Ответ
     *
     * @return array
     * @throws JsonException Если произошла ошибка декдоирования
     */
    public function decodeResponse(ResponseInterface $response): array
    {
        return json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
