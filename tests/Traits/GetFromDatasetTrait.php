<?php
declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace Tests\Traits;

use CashCarryShop\Sizya\OrdersGetterByAdditionalInterface;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Трейт с тестами получения заказов по доп. полям.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see OrdersGetterByAdditionalInterface
 */
trait GetFromDatasetTrait
{
    protected static function getFromDataset(string $key, mixed $default = null): mixed
    {
        $file = ROOT . '/datasets.json';

        if (\file_exists($file)) {
            $datasets = \json_decode(
                \file_get_contents($file),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (isset($datasets[$key])) {
                return \unserialize($datasets[$key]);
            }
        }

        return $default;
    }
}
