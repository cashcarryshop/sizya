<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Moysklad\Traits;

use CashCarryShop\Sizya\Moysklad\Enums\FilterSign;
use CashCarryShop\Sizya\Moysklad\Utils;
use InvalidArgumentException;

/**
 * Трейт с реализацией для сборки фильтров МойСклад.
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait FilterTrait
{
    /**
     * Фильтры
     *
     * @var array<array<string, string|bool|FilterSign>>
     */
    protected array $filters = [];

    /**
     * Установить фильтр
     *
     * @param string            $name        Название фильтра
     * @param string|FilterSign $signOrValue Знак или значение
     * @param string|bool|null  $value       Значение
     *
     * @return static
     * @throws InvalidArgumentException
     */
    public function filter(
        string $name,
        string|bool|FilterSign $signOrValue,
        string|bool|null $value = null
    ): static {
        if ($value) {
            if (is_bool($signOrValue)) {
                throw new InvalidArgumentException(
                    '$signOrValue must be string|FilterSign type when passed $value'
                );
            }

            $this->filters[] = [
                'name' => $name,
                'sign' => is_string($signOrValue)
                    ? FilterSign::from($signOrValue)
                    : $signOrValue,
                'value' => Utils::prepareQueryValue($value)
            ];

            return $this;
        }

        if (is_a($signOrValue, FilterSign::class)) {
            throw new InvalidArgumentException(
                '$signOrValue must be string|bool type when didnt passed $value'
            );
        }

        $this->filters[] = [
            'name' => $name,
            'sign' => FilterSign::EQ,
            'value' => Utils::prepareQueryValue($signOrValue)
        ];

        return $this;
    }
}
