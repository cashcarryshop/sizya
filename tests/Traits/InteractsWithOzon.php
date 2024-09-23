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

use CashCarryShop\Sizya\OrdersGetterByAdditionalInterface;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Трейт с тестами получения заказов по доп. полям.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see OrdersGetterByAdditionalInterface
 */
trait InteractsWithOzon
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
    public function setUp(): void
    {
        if (!$this->credentials) {
            $token    = getenv('OZON_TOKEN');
            $clientId = getenv('OZON_CLIENT_ID');

            if ($token && $clientId) {
                $this->credentials = [
                    'token'    => $token,
                    'clientId' => (int) $clientId
                ];
            }
        }
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
                . 'Env variables OZON_TOKEN and MOYSKLAD_CLIENT_ID is required'
        );
        return null;
    }
}
