<?php
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Helpers
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya;

use CashCarryShop\Sizya\DTO\ErrorDTO;
use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\DTO\ViolationsContainsDTO;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Iterator;
use Throwable;

use function
    array_map,
    asort,
    ksort,
    reset,
    key,
    current,
    next;

/**
 * Набор вспомогательных методов для пакета.
 *
 * @category Helpers
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class Utils
{
    /**
     * На основе переданных данных сопоставить результаты.
     *
     * @param array<array>              $chunks  Чанки соответственно результатам выполнения
     * @param array                     $results Результаты выполнения
     * @param callable(&array, mixed):T $setDto  Функция для получения dto по результатам
     *
     * @return array<T|ByErrorDTO>
     */
    public static function mapResults(array $chunks, array $results, callable $setDto): array
    {
        $items = [];
        foreach ($results as $idx => $result) {
            // Проверяем есть ли ошибки, если да, вместо
            // их вывода добавляем ErrorDTO в результат
            // выполнения.
            if ($result['state'] === 'fulfilled') {
                if ($result['value'] instanceof ResponseInterface) {
                    if ($result['value']->getStatusCode() < 300) {
                        try {
                            $setDto($items, $result['value']);
                            continue;
                        } catch (Throwable $throwable) {
                            $type   = ByErrorDTO::INTERNAL;
                            $reason = $throwable;
                        }
                    } else {
                        $type   = ByErrorDTO::HTTP;
                        $reason = $result['value'];
                    }
                } else {
                    try {
                        $setDto($items, $result['value']);
                        continue;
                    } catch (Throwable $throwable) {
                        $type   = ByErrorDTO::INTERNAL;
                        $reason = $throwable;
                    }
                }
            } else {
                if ($result['reason'] instanceof RequestException) {
                    $type   = ByErrorDTO::HTTP;
                    $reason = $result['reason']->getResponse();
                } else if ($result['reason'] instanceof Throwable) {
                    $type   = ByErrorDTO::INTERNAL;
                    $reason = $result['reason'];
                } else {
                    $type   = ByErrorDTO::UNDEFINED;
                    $reason = $result['reason'];
                }
            }

            foreach ($chunks[$idx] as $values) {
                foreach ($values as $value) {
                    $items[] = ByErrorDTO::fromArray([
                        'value'  => $value,
                        'type'   => $type,
                        'reason' => $reason
                    ]);
                }
            }
        }

        return $items;
    }

    /**
     * Исключить из массива данные, которые
     * не прошли валидацию.
     *
     * Связи с индексами сохраняются.
     *
     * @param T[]                              $values     Значения
     * @param ConstraintViolationListInterface $violations Ошибки валидации
     *
     * @return array<T[], ConstraintViolationInterface[]>
     */
    public static function splitByValidationErrors(
        array                            $values,
        ConstraintViolationListInterface $violations
    ): array {
        $validated = [];
        $errors    = [];

        $position = 0;
        $last     = $violations->count() - 1;

        foreach ($values as $idx => $value) {
            $violation = $last === $position ? null : $violations->get($position);

            if ($violation
                && $violation->getInvalidValue() === $value
            ) {
                if (isset($errors[$idx])) {
                    $errors[$idx]->reason->offsets[] = $position;
                } else {
                    $errors[$idx] = ViolationsContainsDTO::fromArray([
                        'value'      => $value,
                        'offsets'    => [$position],
                        'violations' => $violations
                    ]);
                }

                ++$position;
                continue;
            }

            $validated[$idx] = $value;
        }

        return [$validated, $errors];
    }
}
