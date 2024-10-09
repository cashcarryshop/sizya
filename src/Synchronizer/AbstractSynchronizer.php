<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya
 *
 * PHP version 8
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Synchronizer;

use CashCarryShop\Synchronizer\AbstractSynchronizer as DefaultAbstractSynchronizer;
use CashCarryShop\Sizya\Events\Error;
use CashCarryShop\Sizya\Exceptions\ValidationException;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;

/**
 * Абстрактный класс с основным функционалом
 * для создания синхронизации.
 *
 * @category Synchronizer
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class AbstractSynchronizer extends DefaultAbstractSynchronizer
{
    use InteractsWithValidator;

    /**
     * Переменная, отражающая что синхронизатор выполняется
     *
     * @var bool
     */
    protected bool $running = false;

    /**
     * Проверить, выполняется ли в
     * текущий момент синхронизатор
     *
     * @return bool
     */
    final public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Синхронизировать
     *
     * @param array $settings Настройки для синхронизации
     *
     * @return bool
     */
    final public function synchronize(array $settings = []): bool
    {
        $this->running = true;
        try {
            $settings = \array_replace(
                \array_merge(
                    $this->defaults(), [
                        'throw' => false
                    ]
                ),
                $settings
            );

            $violations = $this->getValidator()
                ->validate($settings, new Assert\Collection(
                    \array_merge(
                        $this->rules(), [
                            'throw' => new Assert\Type('bool')
                        ]
                    )
                ));

            if ($violations->count()) {
                throw new ValidationException(violations: $violations);
            }

            return $this->process($settings);
        } catch (Throwable $exception) {
            $this->running = false;
            $this->event(new Error($exception));

            if ($settings['throw']) {
                throw $exception;
            }
        }

        return true;
    }

    /**
     * Значение по умолчанию для настроек.
     *
     * @return array
     */
    protected function defaults(): array
    {
        return [];
    }

    /**
     * Получить правила валидации настроек
     * для метода synchronize.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Запустить синхронизацию
     *
     * @param array $settings Настройки
     *
     * @return bool
     */
    abstract protected function process(array $settings): bool;
}
