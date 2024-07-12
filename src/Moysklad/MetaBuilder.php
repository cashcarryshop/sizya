<?php
/**
 * Вспомогательный класс для создания МойСклад meta
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

/**
 * Вспомогательный класс для создания МойСклад meta
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class MetaBuilder
{
    /**
     * Стандартный путь до api
     *
     * @var string
     */
    public readonly string $base;

    /**
     * Создать сборщик
     *
     * @param string $base Стандартный путь
     */
    public function __construct(string $base)
    {
        $this->base = $base;
    }

    /**
     * Создать href
     *
     * @param string $path Путь
     *
     * @return string
     */
    public function href(string $path)
    {
        return $this->base . '/' . ltrim($path, '/');
    }

    /**
     * Создать meta
     *
     * @param string $path Путь
     * @param string $type Тип
     *
     * @return array
     */
    public function create(string $path, string $type): array
    {
        return [
            'href' => $this->href($path),
            'type' => $type,
            'mediaType' => 'application/json'
        ];
    }

    /**
     * Создать meta для хранилища (store)
     *
     * @param string $guid GUID хранилища
     *
     * @return array
     */
    public function store(string $guid): array
    {
        return $this->create("entity/store/$guid", 'store');
    }

    /**
     * Создать meta для организации
     *
     * @param string $guid GUID организации
     *
     * @return array
     */
    public function organization(string $guid): array
    {
        return $this->create("entity/organization/$guid", 'organization');
    }

    /**
     * Создать meta для контрагента
     *
     * @param string $guid GUIВ контрагента
     *
     * @return array
     */
    public function counterparty(string $guid): array
    {
        return $this->create("entity/counterparty/$guid", 'counterparty');
    }

    /**
     * Создать meta для контрагента
     *
     * @param string $guid GUIВ контрагента
     *
     * @return array
     */
    public function agent(string $guid): array
    {
        return $this->counterparty($guid);
    }

    /**
     * Создать meta для контракта
     *
     * @param string $guid GUID контракта
     *
     * @return array
     */
    public function contract(string $guid): array
    {
        return $this->create("entity/contract/$guid", 'contract');
    }

    /**
     * Создать meta для канала продаж
     *
     * @param string $guid GUID контракта
     *
     * @return array
     */
    public function salesChannel(string $guid): array
    {
        return $this->create("entity/saleschannel/$guid", 'saleschannel');
    }

    /**
     * Создать meta для заказа покупателя
     *
     * @param string $guid GUID Заказа покупателя
     *
     * @return array
     */
    public function customerorder(string $guid): array
    {
        return $this->create("entity/customerorder/$guid", 'customerorder');
    }

    /**
     * Создать meta для товарв
     *
     * @param string $guid GUID Товара
     *
     * @return array
     */
    public function product(string $guid): array
    {
        return $this->create("entity/product/$guid", 'product');
    }

    /**
     * Создать meta для модификации товарв
     *
     * @param string $guid GUID Модификации
     *
     * @return array
     */
    public function variant(string $guid): array
    {
        return $this->create("entity/variant/$guid", 'variant');
    }
}
