<?php
/**
 * Класс источника
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Ozon;

use Whatis\OzonSeller\Package\DefaultPackage;
use Whatis\OzonSeller\ServiceCompositor;
use Whatis\OzonSeller\Service\IService;
use Whatis\OzonSeller\ServiceManager;
use RuntimeException;

/**
 * Трейт методами, для работы с ozon
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
trait InteractsWithOzon
{
    /**
     * Объект менеджера сервисов Ozon
     *
     * @var ServiceManager
     */
    public readonly ServiceManager $manager;

    /**
     * Идентификатор клиента
     *
     * @var int
     */
    public readonly int $clientId;

    /**
     * Токен
     *
     * @var string
     */
    public readonly string $token;

    /**
     * Получить менеджер свреисов
     *
     * @param ?int    $clientId Идентификатор клиента
     * @param ?string $token    Токен
     *
     * @return ServiceManager
     */
    public function ozon(?int $clientId = null, ?string $token = null): ServiceManager
    {
        if (isset($this->manager)) {
            return $this->manager;
        }

        if (isset($this->clientId, $this->token)) {
            return $this->ozon($this->clientId, $this->token);
        }

        if ($clientId && $token) {
            return $this->manager = ServiceManager::byCreds(
                $this->clientId = $clientId,
                $this->token = $token
            )->package(new DefaultPackage);
        }

        throw new RuntimeException(
            'To receive the ServiceManager (ozon) object for the first '
                . 'time, you must provide [clientId] and [token]'
        );
    }

    /**
     * Использовать какой-то сервис из менеджера
     *
     * @param string  $name     Название сервиса
     * @param ?int    $clientId Идентификатор клиента
     * @param ?string $token    Токен
     *
     * @return IService|ServiceCompositor
     */
    public function service(
        string $name,
        ?int $clientId = null,
        ?string $token = null
    ): IService|ServiceCompositor {
        return $this->ozon($clientId, $token)->service($name);
    }

    /**
     * Получить компоновщик для сервисов
     *
     * @param array $services Сервисы
     *
     * @return ServiceCompositor
     */
    protected function composite(array $services): ServiceCompositor
    {
        return new ServiceCompositor($services);
    }

    /**
     * Сериализовывать всё кроме Ozon
     *
     * @return array
     */
    public function __sleep()
    {
        return array_filter(
            array_map(
                fn ($attribute) => $attribute->getName(),
                (new \ReflectionClass($this))->getProperties()
            ),
            fn ($name) => $name !== 'manager'
        );
    }
}
