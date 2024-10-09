<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
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
 * Конструктор запросов для Ozon Seller.
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
    private array $_path = [];

    /**
     * Query параметры
     *
     * @var array<string, string|array|bool|null>
     */
    private array $_query = [];

    /**
     * Тело запроса
     *
     * @var array|StreamInterface
     */
    private array|StreamInterface $_body = [];

    /**
     * Токен
     *
     * @var string
     */
    private readonly string $_token;

    /**
     * Идентификатор клиента
     *
     * @var int
     */
    private readonly int $_clientId;

    /**
     * Создать сборщик
     *
     * @param string $token    Токен
     * @Param int    $clientId Идентификатор клиента
     */
    public function __construct(string $token, int $clientId)
    {
        $this->_token    = $token;
        $this->_clientId = $clientId;
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
            $item && $this->_path[] = $item;
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
        $this->_query[$name] = $value;
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
        $this->_body = $body;
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
        $url = self::SCHEMA . '://' . self::DOMAIN . '/' . implode('/', $this->_path);
        return $url . sprintf('?%s', http_build_query($this->_query));
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
            'Client-Id' => $this->_clientId,
            'Api-Key' => $this->_token,
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
        if ($this->_body) {
            return is_array($this->_body)
                ? json_encode($this->_body)
                : $this->_body;
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
