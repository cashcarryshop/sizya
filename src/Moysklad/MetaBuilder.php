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
}
