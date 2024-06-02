<?php
/**
 * Класс с вспомогательными методами
 *
 * PHP version 8
 *
 * @category Utils
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya;

/**
 * Класс с вспомогательными методами
 *
 * PHP version 8
 *
 * @category Utils
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/bcashcarryshop/Sizya
 */
class Utils
{
    /**
     * Сформировать чанки по длинне строки
     *
     * @param array $strings Строки
     * @param int   $size    Размер чанка
     *
     * @return array
     */
    public static function splitStringsByChunks(array $strings, $size = 3072): array
    {
        $result = [];
        $currentChunk = [];
        $currentLength = 0;

        foreach ($strings as $string) {
            if ($currentLength + strlen($string) <= $size) {
                $currentChunk[] = $string;
                $currentLength += strlen($string);
                continue;
            }

            $result[] = $currentChunk;
            $currentChunk = [$string];
            $currentLength = strlen($string);
        }

        if (!empty($currentChunk)) {
            $result[] = $currentChunk;
        }

        return $result;
    }

}
