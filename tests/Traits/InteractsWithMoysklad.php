<?php
declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace Tests\Traits;

use GuzzleHttp\Exception\RequestException;

/**
 * Трейт с методами для работы с МойСклад классами.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see OrdersGetterByAdditionalInterface
 */
trait InteractsWithMoysklad
{
    /**
     * Данные авторизации
     *
     * @var array
     */
    protected array $credentials = [];

    /**
     * Настройка тестов МойСклад.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!$this->credentials) {
            $login    = getenv('MOYSKLAD_LOGIN');
            $password = getenv('MOYSKLAD_PASSWORD');
            $token    = getenv('MOYSKLAD_TOKEN');

            if ($login && $password) {
                $this->credentials = [$login, $password];
            } else if ($token) {
                $this->credentials = [$token];
            }
        }
    }

    /**
     * Очистка тестов
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->credentials = [];
    }

    /**
     * Получить сущность через проверки
     *
     * @param callable(array):?object $resolve Получить данные авторизации
     *                                         и вернуть сущность
     *
     * @return ?object
     */
    protected function getEntity(callable $resolve): ?object
    {
        if ($this->credentials) {
            try {
                return $resolve($this->credentials);
            } catch (RequestException $exception) {
                $response = $exception->getResponse();

                if ($response->getStatusCode() === 403) {
                    $this->markTestSkipped('Invalid credentials. cannot complete test');
                    return null;
                }

                $this->markTestSkipped(
                    sprintf(
                        'Moysklad request error. Code [%d], Body [%s]',
                        $response->getStatusCode(),
                        $response->getBody()->getContents()
                    )
                );

                return null;
            }
        }

        $this->markTestSkipped(
            'Credentials not set. Cannot complete test. '
                . 'Env variables MOYSKLAD_LOGIN, MOYSKLAD_PASSWORD '
                . 'or MOYSKLAD_TOKEN is required'
        );
        return null;
    }
}
