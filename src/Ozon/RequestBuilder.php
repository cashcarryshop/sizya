<?php
/**
 * Сборщик RequestInterface для Ozon
 *
 * PHP version 8
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Ozon;

use CashCarryShop\Sizya\Http\Enums\Method;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;

/**
 * Сборщик RequestInterface для Ozon
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class RequestBuilder
{
    public const SCHEMA = 'https';
    public const DOMAIN = 'api-seller.ozon.ru';

    /**
     * Путь
     *
     * @var array<string>
     */
    protected array $path = [];

    /**
     * Query параметры
     *
     * @var array<string, string|array|bool|null>
     */
    protected array $query = [];

    /**
     * Тело запроса
     *
     * @var array|StreamInterface
     */
    protected array|StreamInterface $body = [];

    /**
     * Токен
     *
     * @var string
     */
    public readonly string $token;

    /**
     * Идентификатор клиента
     *
     * @var int
     */
    public readonly int $clientId;

    /**
     * Создать сборщик
     *
     * @param string $token    Токен
     * @param int    $clientId Идентификатор клиента
     */
    public function __construct(string $token, int $clientId)
    {
        $this->token = $token;
        $this->clientId = $clientId;
    }

    /**
     * Установить элемент пути по которому нужно
     * сделать запрос (сгенерировать объект Request)
     *
     * @param string $method Метод
     *
     * @return static
     */
    public function point(string $method): static
    {
        foreach (explode('/', $method) as $item) {
            $item && $this->path[] = $item;
        }

        return $this;
    }

    /**
     * Установить query параметр
     *
     * @param string                 $name  Название
     * @param string|bool|array|null $value Значение
     *
     * @return static
     */
    public function query(string $name, string|bool|array|null $value = null): static
    {
        $this->query[$name] = $value;
        return $this;
    }

    /**
     * Тело запроса
     *
     * @param array|StreamInterface $body Тело
     *
     * @return static
     */
    public function body(array|StreamInterface $body): static
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Собрать метод
     *
     * @param string|Method $method Метод
     *
     * @return string
     */
    private function _buildMethod(string|Method $method): string
    {
        $method = is_string($method)
            ? Method::from(strtoupper($method))
            : $method;

        return $method->value;
    }

    /**
     * Собрать url
     *
     * @return string
     */
    private function _buildUrl(): string
    {
        $url = self::SCHEMA . '://' . self::DOMAIN . '/' . implode('/', $this->path);
        return $url . sprintf('?%s', http_build_query($this->query));
    }

    /**
     * Собрать заголовки
     *
     * @return array
     * @throws InvalidArgumentException
     */
    private function _buildHeaders(): array
    {
        return [
            'Host' => self::DOMAIN,
            'Client-Id' => $this->clientId,
            'Api-Key' => $this->token,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Собрать тело запроса
     *
     * @return string|StreamInterface
     */
    private function _buildBody(): string|StreamInterface
    {
        if ($this->body) {
            return is_array($this->body)
                ? json_encode($this->body)
                : $this->body;
        }

        return '';
    }

    /**
     * Собрать
     *
     * @param string|Method $method Метод
     *
     * @return Request
     */
    public function build(string|Method $method): Request
    {
        return new Request(
            $this->_buildMethod($method),
            $this->_buildUrl(),
            $this->_buildHeaders(),
            $this->_buildBody()
        );
    }
}
