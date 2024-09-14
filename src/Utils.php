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
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

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
     * Обрабатывает ошибки, которые могут возникнуть в результаты
     * выполнения, вместо них устанавливает ErrorDTO.
     *
     * Учитывает отсутствие ответа по переданному значению,
     * вместо него устанавливает ErrorDTO::NOT_FOUND.
     *
     * S - generic тип обозначающий "Search"
     * R - generic тип обозначающий "Result"
     * D - generic тип обозначающий "DTO"
     *
     * @param S[]             $chunks  Чанки соответственно результатам выполнения
     * @param R[]             $results Результаты выполнения
     * @param callable(R):D[] $getDtos Функция для получения dto на выход
     * @param callable(D):S   $pluck   Получить из dto из функции $getDtos найденное значение
     *
     * @return array<D|ByErrorDTO>
     */
    public static function mapResults(
        array    $chunks,
        array    $results,
        callable $getDtos,
        callable $pluck
    ): array {
        $items = [];

        foreach ($results as $idx => $result) {
            // Объявляем переменные, если их нет. Если есть,
            // то сбрасываем значения чтобы оператинвую память
            // не занимали.
            $reason = $type = $found = $foundValues = $chunk = null;

            if ($result['state'] === 'fulfilled') {
                if ($result['value'] instanceof ResponseInterface
                    && $result['value']->getStatusCode() >= 300
                ) {
                    $type   = ByErrorDTO::HTTP;
                    $reason = $result['value'];
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

            // Ошибок не найдено
            if (\is_null($reason)) {
                try {
                    $found       = $getDtos($result['value']);
                    $foundValues = \array_map($pluck, $found);
                    $chunk       = $chunks[$idx];

                    \asort($foundValues, SORT_STRING);
                    \asort($chunk,       SORT_STRING);

                    \reset($foundValues);
                    foreach ($chunk as $value) {
                        if (\current($foundValues) === $value) {
                            do {
                                $items[] = $found[\key($foundValues)];
                                \next($foundValues);
                            } while (\current($foundValues) === $value);

                            continue;
                        }

                        $items[] = ByErrorDTO::fromArray([
                            'value' => $value,
                            'type'  => ByErrorDTO::NOT_FOUND,
                        ]);
                    }

                    continue;
                } catch (Throwable $throwable) {
                    $type   = ByErrorDTO::INTERNAL;
                    $reason = $throwable;
                }
            }

            // С ошибкой
            foreach ($chunks[$idx] as $value) {
                $items[] = ByErrorDTO::fromArray([
                    'value'  => $value,
                    'type'   => $type,
                    'reason' => $reason
                ]);
            }

            continue;
        }

        return $items;
    }

    /**
     * Разделить массив на элементы, которые прошли
     * правила валидации и которые не прошли.
     *
     * Связи с индексами сохраняются.
     *
     * @param T[]                              $values     Значения
     * @param ConstraintViolationListInterface $violations Ошибки валидации
     *
     * @return array<T[], ViolationsContainsDTO[]>
     */
    public static function splitByValidationErrors(
        array                            $values,
        ConstraintViolationListInterface $violations
    ): array {
        $validated = [];
        $errors    = [];

        $position = 0;
        $last     = $violations->count();

        foreach ($values as $idx => $value) {
            $violation = $last <= $position ? null : $violations->get($position);

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
