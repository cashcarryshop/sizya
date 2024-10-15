<?php declare(strict_types=1);
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

namespace CashCarryShop\Sizya\Tests\Traits;

use CashCarryShop\Sizya\DTO\ByErrorDTO;

/**
 * Трейт с тестами обновления заказов.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 *
 * @see OrdersUpdaterInterface
 */
trait ByErrorAssertions
{
    /**
     * Сопоставить ошибки.
     *
     * @param ByErrorDTO[] $expected Ожидаемые
     * @param ByErrorDTO[] $errors   Полученные
     *
     * @return void
     */
    protected function assertByErrors(array $expected, array $errors): void
    {
        \array_multisort(
            \array_column($expected, 'value'),
            SORT_REGULAR,
            $expected
        );

        \array_multisort(
            \array_column($errors, 'value'),
            SORT_REGULAR,
            $errors
        );

        \reset($expected);
        foreach ($errors as $error) {
            $expects = \current($expected);

            $this->assertEquals(
                $expects->type,
                $error->type,
                'By error types must be equals'
            );

            $this->assertEquals(
                $expects->value,
                $error->value,
                'Values by error must be equals'
            );

            \next($expected);
        }
    }
}
