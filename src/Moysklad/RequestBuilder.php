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

use CashCarryShop\Sizya\Http\Utils;
use CashCarryShop\Sizya\Http\Enums\Method;
use CashCarryShop\Sizya\Moysklad\Traits\FilterTrait;
use CashCarryShop\Sizya\Moysklad\Traits\OffsetTrait;
use CashCarryShop\Sizya\Moysklad\Traits\ParamTrait;
use CashCarryShop\Sizya\Moysklad\Traits\OrderTrait;
use CashCarryShop\Sizya\Moysklad\Traits\LimitTrait;
use CashCarryShop\Sizya\Moysklad\Traits\ExpandTrait;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;

/**
 * Конструктор запросов для МойСклад
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class RequestBuilder
{
    use FilterTrait;
    use OffsetTrait;
    use ParamTrait;
    use OrderTrait;
    use LimitTrait;
    use ExpandTrait;

    public const SCHEMA = 'https';
    public const DOMAIN = 'api.moysklad.ru';
    public const PATH = 'api/remap/1.2';

    /**
     * Путь
     *
     * @var array<string>
     */
    private array $_path = [];

    /**
     * Тело запроса
     *
     * @var array|StreamInterface
     */
    private array|StreamInterface $_body = [];

    /**
     * Данные авторизации
     *
     * @var array
     */
    private readonly array $_credentials;

    /**
     * Создать сборщик
     *
     * @param array $credentials Данные авторизации
     */
    public function __construct(array $credentials)
    {
        $this->_credentials = $credentials;
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
     * Собрать параметры
     *
     * @param array<array<string, string>> $params Параметры
     *
     * @return string
     */
    private function _buildParams(array $params): string
    {
        $result = '';

        foreach ($params as $index => $param) {
            if ($index) {
                $result .= ';';
            }

            $sign = array_key_exists('sign', $param)
                ? (is_string($param['sign']) ? $param['sign'] : $param['sign']->value)
                : '=';

            $result .= sprintf(
                '%s%s%s',
                $params['name'],
                $sign,
                $params['value']
            );
        }

        return $result;
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
        $url = self::SCHEMA . '://' . self::DOMAIN . '/' . self::PATH;
        $url .= '/' . \implode('/', $this->_path);

        $query = $this->params;

        $filters = implode(';', array_map(
            fn ($filter) => sprintf(
                '%s%s%s',
                $filter['name'],
                $filter['sign']->value,
                $filter['value']
            ),
            $this->filters
        ));

        $expand = \implode(';', $this->expand);
        $order = \implode(';', array_map(
            fn ($name, $order) => sprintf('%s,%s', $name, $order->value),
            \array_keys($this->order), $this->order
        ));

        $filters && $query['filter'] = $filters;
        $order && $query['order'] = $order;
        $expand && $query['expand'] = $expand;
        $this->limit && $query['limit'] = $this->limit;
        $this->offset && $query['offset'] = $this->offset;

        if ($query) {
            $url .= \sprintf('?%s', \http_build_query($query));
        }

        return $url;
    }

    /**
     * Собрать заголовки
     *
     * @return array
     * @throws InvalidArgumentException
     */
    private function _buildHeaders(): array
    {
        $count = \count($this->_credentials);

        if (!\in_array($count, [1, 2])) {
            throw new InvalidArgumentException(
                'The size of the credential array must be equal to 1 for a token '
                    . "or 2 for a login-password, $count provided"
            );
        }

        $authorization = 'Bearer ' . $this->_credentials[0];
        if ($count === 2) {
            $authorization = 'Basic ' . base64_encode(
                $this->_credentials[0] . ':' . $this->_credentials[1]
            );
        }

        return [
            'Content-Type' => 'application/json',
            'Accept-Encoding' => 'gzip',
            'Authorization' => $authorization
        ];
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
            Utils::getStream($this->_body, 'json_encode')
        );
    }
}
