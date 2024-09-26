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
    /**
     * Сгенерировать guidv4
     *
     * @param ?string $data Битовые данные длинной 128 битов
     *
     * @return string
     */
    protected static function guidv4(?string $data = null): string
    {
        // Generate 16 bytes (128 bits) of random data
        // or use the data passed into the function.
        $data = $data ?? \random_bytes(16);
        assert(\strlen($data) == 16);

        // Set version to 0100
        $data[6] = \chr(\ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = \chr(\ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return \vsprintf('%s%s-%s-%s-%s-%s%s%s', \str_split(\bin2hex($data), 4));
    }

    /**
     * Сгенерировать фейковый артикул.
     *
     * @param string $prefix Префикс
     * @param ?int   $index  Индекс
     * @param int    $length Длина
     *
     * @return string
     */
    protected static function fakeArticle(
        string $prefix = 'CCS',
        ?int   $index  = null,
        int    $length = 5
    ): string {
        return $prefix . \str_pad(
            $index ?? (string) \random_int(0, 1000),
            $length,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Сгенерировать рандомную строку.
     *
     * @param int $length Длина строки
     *
     * @return string
     */
    protected static function fakeString(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = \strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[\random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

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
