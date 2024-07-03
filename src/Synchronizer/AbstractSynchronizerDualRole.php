<?php
/**
 * Элемент синхронизации
 *
 * PHP version 8
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Synchronizer;

use CashCarryShop\Synchronizer\SynchronizerDualRoleInterface;

/**
 * Элемент синхронизации
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class AbstractSynchronizerDualRole implements SynchronizerDualRoleInterface
{
    /**
     * Настройки
     *
     * @var array
     */
    protected array $settings;

    /**
     * Создание элемента синхронизации
     *
     * @param array $settings Настройки
     */
    final public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->initialize($settings);
    }

    /**
     * Иницилизировать синхронизатор
     *
     * @param array $settings Настройки
     *
     * @return void
     */
    abstract protected function initialize(array $settings): void;

    /**
     * Получить настройку(и)
     *
     * Есть вомозжность получить настройку,
     * используя нотацию dot:
     *
     * ```php
     * $setting = $this->getSettings('path.to.setting', 'default');
     * ```
     *
     * @param string|int|null $key     Ключ по которому получить настройку(и)
     * @param mixed           $default Значение по-умолчанию
     *
     * @return mixed
     */
    final public function getSettings(mixed $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->settings;
        }

        if (array_key_exists($key, $this->settings)) {
            return $this->settings[$key];
        }

        if (!is_string($key) || strpos($key, '.') === false) {
            return $default;
        }

        $settings = $this->settings;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($segment) || !array_key_exists($settings, $segment)) {
                return $default;
            }

            $settings = &$settings[$segment];
        }

        return $settings;
    }
}
