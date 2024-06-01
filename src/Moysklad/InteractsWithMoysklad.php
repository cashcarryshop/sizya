<?php
/**
 * Класс источника
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Moysklad;

use Evgeek\Moysklad\MoySklad;
use Evgeek\Moysklad\Api\Query\QueryBuilder;
use RuntimeException;

/**
 * Класс с настройками и логикой получения
 * остатков Moysklad (source)
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   CashCarryShop <cashcarryshop@yandex.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
trait InteractsWithMoysklad
{
    /**
     * Объект МойСклад, который иницилизируется
     * 1 раз и используется
     *
     * @var MoySklad
     */
    public readonly MoySklad $moysklad;

    /**
     * Данные авторизации
     *
     * @var array
     */
    public readonly array $credentials;

    /**
     * Получить объект МойСклад
     *
     * @param ?array $credentials Данные авторизации
     *
     * @return MoySklad
     * @throw  RuntimeException
     */
    public function moysklad(?array $credentials = null): MoySklad
    {
        if (isset($this->moysklad)) {
            return $this->moysklad;
        }

        if (isset($this->credentials)) {
            return $this->moysklad($this->credentials);
        }

        if ($credentials) {
            return $this->moysklad = new MoySklad($this->credentials = $credentials);
        }

        throw new RuntimeException(
            'To receive the MoySklad object for the first '
                .'time, you must provide [credentials]'
        );
    }

    /**
     * Получить QueryBuilder
     *
     * @return QueryBuilder
     */
    public function query(): QueryBuilder
    {
        return $this->moysklad(...func_get_args())->query();
    }

    /**
     * Сериализовывать всё кроме EventDispatcher
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
            fn ($name) => $name !== 'moysklad'
        );
    }
}
