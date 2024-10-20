<?php declare(strict_types=1);
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

use CashCarryShop\Sizya\DTO\ByErrorDTO;
use CashCarryShop\Sizya\DTO\DTOInterface;
use CashCarryShop\Sizya\Exceptions\BadResponseException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraint;
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
     * Функия $getData должна возвращать массив:
     *
     * - dtos:   (DTOInterface[]) С данными которые будут на выходе
     * - values: (string[])       Данные по которым значения были найдены
     *
     * S - generic тип обозначающий "Search"
     * R - generic тип обозначающий "Result"
     *
     * @param S[]               $chunks  Чанки соответственно результатам выполнения
     * @param R[]               $results Результаты выполнения
     * @param callable(R):array $getData   Функция для получения необходимых данных
     *
     * @return array<DTOInterface|ByErrorDTO>
     */
    public static function mapResults(
        array    $chunks,
        array    $results,
        callable $getData,
    ): array {
        $items = [];

        foreach ($results as $idx => $result) {
            // Объявляем переменные, если их нет. Если есть,
            // то сбрасываем значения чтобы оператинвую память
            // не занимали.
            $reason = $type = $data = $chunk = null;

            if ($result['state'] === 'fulfilled') {
                if ($result['value'] instanceof ResponseInterface
                    && $result['value']->getStatusCode() >= 300
                ) {
                    $type   = ByErrorDTO::HTTP;
                    $reason = new BadResponseException($result['value']);
                }
            } else {
                if ($result['reason'] instanceof RequestException) {
                    $type   = ByErrorDTO::HTTP;
                    $reason = new BadResponseException($result['reason']->getResponse());
                } else if ($result['reason'] instanceof Throwable) {
                    $type   = ByErrorDTO::INTERNAL;
                    $reason = $result['reason'];
                } else {
                    $type   = ByErrorDTO::UNDEFINED;
                    $reason = $result['reason'];
                }
            }

            // Ошибок не найдено
            if ($reason === null) {
                try {
                    $chunk = $chunks[$idx];
                    $data  = $getData($result['value'], $chunk);

                    \asort($data['values'], SORT_REGULAR);
                    \asort($chunk,          SORT_REGULAR);

                    \reset($chunk);
                    \reset($data['values']);

                    $previous = false;

                    foreach ($chunk as $value) {
                        if ($previous === $value) {
                            $items[] = ByErrorDTO::fromArray([
                                'value' => $value,
                                'type'  => ByErrorDTO::DUPLICATE
                            ]);

                            continue;
                        }

                        if (\current($data['values']) === $value) {
                            do {
                                $items[] = $data['dtos'][\key($data['values'])];
                            } while (\next($data['values']) === $value);

                            $previous = $value;

                            continue;
                        }

                        $items[] = ByErrorDTO::fromArray([
                            'value' => $previous = $value,
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
     * @param ValidatorInterface $validator   Валидатор
     * @param T[]                $values      Которые нужно отвалидировать
     * @param Constraint[]       $constraints Правила валидации для каждого значения
     *
     * @return array<T[], ByErrorDTO[]>
     */
    public static function splitByValidationErrors(
        ValidatorInterface $validator,
        array              $values,
        array              $constraints
    ): array {
        $errors = [];

        foreach ($values as $idx => $value) {
            $violations = $validator->validate($value, $constraints);

            if ($violations->count()) {
                $errors[$idx] = ByErrorDTO::fromArray([
                    'type'   => ByErrorDTO::VALIDATION,
                    'reason' => $violations,
                    'value'  => $value
                ]);
                unset($values[$idx]);
            }
        }

        return [$values, $errors];
    }

    /**
     * Разделить массив по чанкам и сразу создать
     * promise для отправки данных.
     *
     * @param array                                $items      Элементы
     * @param callable                             $getData    Получить данные одного элемента для promise
     * @param callable                             $getPromise Получить Promise
     * @param ?callable(int, int):bool $shouldPush Проверка следует ли добавить новый чанк
     *
     * @return array<string, array>
     */
    public static function getByChunks(
        array    $items,
        callable $getData,
        callable $getPromise,
        ?callable $shouldPush = null
    ) {
        $shouldPush ??= static fn ($counter) => $counter === 99;

        $count   = \count($items);
        $counter = 0;

        $chunk  = [];
        $chunks = [];

        $promises = [];
        $data     = [];

        \reset($items);
        for ($i = 0; $i < $count; ++$i) {
            $key   = \key($items);
            $value = \current($items);

            $data[]      = $getData($value, $key);
            $chunk[$key] = $value;

            \next($items);
            if ($shouldPush($counter, $key, $i)) {
                $counter    = 0;
                $promises[] = $getPromise($data);
                $chunks[]   = $chunk;
                $chunk      = [];
                $data       = [];
            } else if ($chunk && $i + 1 === $count) {
                $chunks[]   = $chunk;
                $promises[] = $getPromise($data);
            }
        }

        return [
            'promises' => $promises,
            'chunks'   => $chunks
        ];
    }

    /**
     * Установить значение в массив по ключу
     * если в объекте оно не null.
     *
     * @param string  $key       Ключ
     * @param object  $object    Объект
     * @param array   $data      Ссылка на данные
     * @param ?string $targetKey Целевой ключ (по-умолчанию имеен значение $key)
     *
     * @return bool Было ли значение установлено
     */
    public static function setIfNotNull(
        string $key,
        object $object,
        array  &$data,
        ?string $targetKey = null
    ): bool {
        $targetKey ??= $key;

        if ($object->$key === null) {
            return false;
        }

        $data[$targetKey] = $object->$key;
        return true;
    }
}
