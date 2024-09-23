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
    protected static array $credentials = [];

    /**
     * Настройка тестов МойСклад.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        $login    = getenv('MOYSKLAD_LOGIN');
        $password = getenv('MOYSKLAD_PASSWORD');
        $token    = getenv('MOYSKLAD_TOKEN');

        if ($login && $password) {
            static::$credentials = [$login, $password];
        } else if ($token) {
            static::$credentials = [$token];
        } else {
            $this->markTestSkipped('No credentials provided. Skipping tests');
        }

        static::markSkippedIfBadResponse(
            fn () => static::setUpBeforeClassByMoysklad(static::$credentials)
        );
    }

    /**
     * Настройка тестов МойСклад с перехватом
     * ошибки от api.
     *
     * @param array $credentials Данные авторизации
     *
     * @return void
     */
    protected static function setUpBeforeClassByMoysklad(array $credentials): void
    {
        // ...
    }

    /**
     * Сбросить данные
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        static::$credentials = [];
        static::tearDownAfterClassByMoysklad();
    }

    /**
     * Сбросить данные Ozon
     *
     * @return void
     */
    protected static function tearDownAfterClassByMoysklad(): void
    {
        // ...
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
        return static::markSkippedIfBadResponse(
            $resolve,
            fn ($message) => $this->markTestSkipped($message)
        );
    }

    /**
     * Проверить стоит ли запускать тест, проверая response от МойСклад.
     *
     * @param callable          $resolve Функция для вызова и возвращения значения
     * @param ?callable(string) $catch   Перехватить ошибку
     *
     *
     * @return mixed Результат из
     */
    protected static function markSkippedIfBadResponse(callable $resolve, ?callable $catch = null): mixed
    {
        $catch ??= fn ($message) => static::markTestSkipped($message);

        if (static::$credentials) {
            try {
                return $resolve(static::$credentials);
            } catch (RequestException $exception) {
                $response = $exception->getResponse();

                if (\in_array($response->getStatusCode(), [401, 403])) {
                    $catch(
                        \sprintf(
                            'Invalid credentials. cannot complete test. Code: [%d], Body: [%s]',
                            $response->getStatusCode(),
                            $response->getBody()->getContents()
                        )
                    );

                    return null;
                }


                $catch(
                    \sprintf(
                        'Moysklad request error. Code [%d], Body [%s]',
                        $response->getStatusCode(),
                        $response->getBody()->getContents()
                    )
                );
                return null;
            }
        }

        $catch(
            'Credentials not set. Cannot complete test. '
                . 'Env variables MOYSKLAD_LOGIN, MOYSKLAD_PASSWORD '
                . 'or MOYSKLAD_TOKEN is required'
        );
    }
}
