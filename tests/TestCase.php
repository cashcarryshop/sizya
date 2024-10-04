<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Main
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Tests;

use CashCarryShop\Sizya\Tests\Traits\InteractsWithFakeData;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Собственный TestCase.
 *
 * @category Main
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class TestCase extends BaseTestCase
{
    use InteractsWithFakeData;

    /**
     * Сгенерировать массивы с данными для dataProvider.
     *
     * Массив $options принимает:
     *
     * - validSize:         (int)      Размер валидных значений (по-умолчанию 30)
     * - invalidSize        (int)      Размер неверных значений (по-умолчанию 30)
     * - provideSize:       (int)      Размер отдельного чанка (по-умаолчанию 30)
     * - validGenerator:    (callable) Генератор валидных значений
     *                                 (по-умаолчанию через static::guidv4)
     * - invalidGenerator:  (callable) Генератор неверных значений
     *                                 (по-умаолчанию устанавливается validGenerator)
     * - additionalInvalid: (array)    Дополнительные невалидные значения
     *                                 (по-умолчанию пустой массив)
     *
     * @param array $options Опции
     *
     * @return array<mixed[], mixed[]> Возвращает общий массив и неверные значения
     */
    protected static function generateProvideData(array $options = []): array
    {
        $options = \array_replace(
            [
                'validSize'         => 30,
                'invalidSize'       => 30,
                'provideSize'       => 30,
                'validGenerator'    => fn () => static::guidv4(),
                'invalidGenerator'  => $options['validGenerator'] ?? fn () => static::guidv4(),
                'additionalInvalid' => []
            ], $options
        );

        $validValues = \array_map(
            $options['validGenerator'],
            \array_fill(0, $options['validSize'], null)
        );

        $invalidValues = \array_merge(
            \array_map(
                $options['invalidGenerator'],
                \array_fill(0, $options['invalidSize'], null)
            ),
            $options['additionalInvalid']
        );

        $values = \array_merge($validValues, $invalidValues);
        \shuffle($values);

        return [
            'values'   => $values,
            'valid'    => $validValues,
            'invalid'  => $invalidValues,
            'provides' => \array_chunk($values, $options['provideSize'])
        ];
    }
}
