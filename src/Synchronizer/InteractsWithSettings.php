<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
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

use Symfony\Component\Validator\Constraints as Assert;
use CashCarryShop\Sizya\Exceptions\ValidationException;

/**
 * Трейт с реализацией методов для
 * взаимодействия с настройками.
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait InteractsWithSettings
{
    use InteractsWithValidator;

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
     *
     * @throws ValidationException
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->_validate();
    }

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

        if (\array_key_exists($key, $this->settings)) {
            return $this->settings[$key];
        }

        if (!\is_string($key) || \strpos($key, '.') === false) {
            return $default;
        }

        $settings = $this->settings;
        foreach (\explode('.', $key) as $segment) {
            if (!\is_array($segment) || !\array_key_exists($settings, $segment)) {
                return $default;
            }

            $settings = $settings[$segment];
        }

        return $settings;
    }

    /**
     * Валидировать настройки
     *
     * @return void
     * @throws ValidationException
     */
    private function _validate(): void
    {
        $violations = $this->getValidator()
            ->validate(
                $this->settings,
                new Assert\Collection($this->rules())
            );

        if ($violations->count()) {
            throw new ValidationException(violations: $violations);
        }
    }

    /**
     * Правила валидации для настроек
     *
     * @return array
     */
    abstract protected function rules(): array;
}
