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

/**
 * Трейт с методом для разделения данных
 * по классам и проверки соответствия
 * им.
 *
 * @category TestTraits
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
trait AssertAndSplitByClassesTrait
{
    /**
     * Разделить какие-либо сущности от ByErrorDTO
     *
     * @param array $items   Элементы
     * @param array $classes Классы по которым делить
     *
     * @return array
     */
    protected function assertAndSplitByClasses(array $items, array $classes): array
    {
        $splitted = \array_combine(
            \array_keys($classes),
            \array_map(static fn () => [], $classes)
        );

        foreach ($items as $item) {
            foreach ($classes as $idx => $class) {
                if (\is_a($item, $class)) {
                    $splitted[$idx][] = $item;
                    continue 2;
                }
            }

            $this->fail(
                sprintf(
                    'Item must be implementation of %s classes, [%s] given',
                    \array_reduce(
                        $classes,
                        static fn ($carry, $item) =>
                            (null === $carry ?: "{$carry}, ")
                            . "[{$item}]"
                    ),
                    \is_object($item)
                        ? \get_class($item)
                        : (
                            \is_array($item)
                                ? 'Array'
                                : $item
                        )
                )
            );
        }

        return $splitted;
    }
}
