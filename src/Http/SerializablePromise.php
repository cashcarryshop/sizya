<?php
/**
 * Интерфейс отправителя запросов
 *
 * PHP version 8
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Http;

use GuzzleHttp\Promise\PromiseInterface;
use CashCarryShop\Sizya\Utils;
use Laravel\SerializableClosure\SerializableClosure;
use ReflectionClass;
use Closure;

/**
 * Интерфейс отправителя запросов
 *
 * @category Http
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class SerializablePromise implements PromiseInterface
{
    /**
     * Promise
     *
     * @var PromiseInterface
     */
    private PromiseInterface $promise;

    /**
     * Создание экземлпляра Promise
     *
     * @param PromiseInterface $promise Promise
     */
    public function __construct(PromiseInterface $promise)
    {
        $this->_prepareObjectforSerialization($promise, $promise);
        $this->promise = $promise;
    }

    /**
     * Проверить что значение уже сериализуемое
     *
     * @param mixed $value Значение
     *
     * @return bool
     */
    private function _alreadySerializable(mixed $value): bool
    {
        return is_a($value, SerializableClosure::class)
            || is_a($value, static::class);
    }

    /**
     * Обработать объект для сериализации
     *
     * @param object $object Объект
     *
     * @return void
     */
    private function _prepareObjectForSerialization(object $object): void
    {
        static $already;
        $already ??= [];

        static $level;
        $level ??= 0;
        ++$level;

        $reflector = new ReflectionClass($object);

        foreach ($reflector->getProperties() as $property) {
            $value = $property->getValue($object);
            if ($this->_alreadySerializable($value)) {
                continue;
            }

            if (is_callable($value)) {
                if (is_array($value)) {
                    $this->_prepareArrayForSerialization($value);
                    $property->setValue($object, $value);
                    continue;
                }

                $property->setValue($object, Utils::getSerializableCallable($value));
                continue;
            }

            foreach ($already as $item) {
                if ($item === $value) {
                    continue 2;
                }
            }

            $already[] = $value;

            if (is_array($value)) {
                $this->_prepareArrayForSerialization($value);
                $property->setValue($object, $value);
                continue;
            }

            if (is_object($value)) {
                var_dump(get_class($value));
                $this->_prepareObjectForSerialization($value);
            }
        }

        if ($level === 1) {
            $already = $level = null;
        }
    }

    /**
     * Рекурсивно обработать значения в массиве
     *
     * @param array $array Ссылка на значение
     *
     * @return void
     */
    private function _prepareArrayForSerialization(array &$array): void
    {
        foreach ($array as $key => $value) {
            if ($this->_alreadySerializable($value)) {
                continue;
            }

            if (is_callable($value)) {
                $array[$key] = Utils::getSerializableCallable($value);
                continue;
            }


            if (is_array($value)) {
                $this->_prepareArrayForSerialization($value);
                continue;
            }

            if (is_object($value)) {
                $this->_prepareObjectForSerialization($value);
                $array[$key] = $value;
            }
        }
    }

    /**
     * Установка обработчиков
     *
     * @param ?callable $onFulfilled Обработчик на заполнение Promise
     * @param ?callable $onRejected  Обработчик ошибки
     *
     * @return PromiseInterface
     */
    public function then(
        ?callable $onFulfilled = null,
        ?callable $onRejected = null
    ): PromiseInterface {
        return new static($this->promise->then(
            Utils::getSerializableCallable($onFulfilled),
            Utils::getSerializableCallable($onRejected)
        ));
    }

    /**
     * Установка обработчика на reject
     *
     * @param callable $onRejected Обработчик
     *
     * @return PromiseInterface
     */
    public function otherwise(callable $onRejected): PromiseInterface
    {
        return new static($this->promise->otherwise(
            Utils::getSerializableCallable($onRejected)
        ));
    }

    /**
     * Получить статус Promise
     *
     * @return string
     */
    public function getState(): string
    {
        return $this->promise->getState();
    }

    /**
     * Установить результат выполнения
     *
     * @param mixed $value Значение
     *
     * @return void
     */
    public function resolve($value): void
    {
        $this->promise->resolve($value);
    }

    /**
     * Установить причину ошибки
     *
     * @param mixed $reason Причина
     *
     * @return void
     */
    public function reject($reason): void
    {
        $this->promise->reject($reason);
    }

    /**
     * Закрыть Promise
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->promise->cancel();
    }

    /**
     * Ожидать выполнение
     *
     * @param bool $unwrap Развернуть ли результат Promise
     *
     * @return mixed
     */
    public function wait(bool $unwrap = true)
    {
        return $this->promise->wait($unwrap);
    }
}
