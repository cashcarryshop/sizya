<?php
/**
 * Класс остатков
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Moysklad;

use Respect\Validation\Validator as v;
use CashCarryShop\Synchronizer\SynchronizerSourceInterface;
use CashCarryShop\Synchronizer\SynchronizerTargetInterface;
use CashCarryShop\Sizya\Promise\InteractsWithDeferred;
use CashCarryShop\Promise\PromiseInterface;

/**
 * Класс с настройками и логикой получения
 * остатков Moysklad
 *
 * PHP version 8
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
class Stocks implements SynchronizerSourceInterface, SynchronizerTargetInterface
{
    use InteractsWithDeferred;
    use InteractsWithMoysklad;

    /**
     * Настройки
     *
     * @var array
     */
    public readonly array $settings;

    /**
     * Создание экземпляра источника
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        $chSince = $settings['changedSince'] ?? null;

        if (is_a($chSince, \DateTimeInterface::class)) {
            $chSince = $chSince->format('Y-m-d H:i:s');
        }

        $this->settings = [
            'quantityMode' => $settings['quantityMode'] ?? 'all',
            'archive' => $settings['archive'] ?? false,
            'groupBy' => $settings['groupBy'] ?? 'variant',
            'login' => $settings['login'] ?? '',
            'password' => $settings['password'] ?? '',
            'token' => $settings['token'] ?? '',
            'changedSince' =>  $chSince,
            'zeroLines' => $settings['zeroLines'] ?? false
        ];

        v::keySet(
            v::key('quantityMode', v::stringType()->in([
                'all',
                'positiveOnly',
                'negativeOnly',
                'empty',
                'nonEmpty',
                'underMinimum'
            ])),
            v::key('changedSince', v::anyOf(
                v::dateTime('Y-m-d H:i:s'),
                v::equals(null)
            )),
            v::key('archive', v::anyOf(v::stringType()->equals('all'), v::boolType())),
            v::key('groupBy', v::stringType()->in('product variant consignment')),
            v::key('token', v::stringType()),
            v::key('login', v::stringType()),
            v::key('password', v::stringType()),
            v::key('zeroLines', v::boolType())
        )->assert($this->settings);

        if (v::key('token', v::notEmpty())->validate($this->settings)) {
            return $this->moysklad([$this->settings['token']]);
        }

        v::key('login', v::notEmpty())
            ->key('password', v::notEmpty())
            ->assert($settings);

        $this->moysklad([$this->settings['login'], $this->settings['password']]);
    }

    /**
     * Получить остатки товаров
     *
     * Получение производиться до тех
     * пор, пока не будут перебраны
     * все остатки товаров в МойСклад
     *
     * @param array $stores Идентификаторы храналищ
     * @param int   $offset Отступ элементов
     *
     * @return PromiseInterface
     */
    public function get(array $stores = [], int $offset = 0): PromiseInterface
    {
        return $this->resolveThrow(function ($deferred) use ($offset) {
            $query = $this->query()
                ->report()
                ->method('stock')
                ->method('all')
                ->limit(1000)
                ->offset($offset)
                ->filter('quantityMode', $this->settings['quantityMode'])
                ->param('groupBy', $this->settings['groupBy']);

            foreach ($stores as $store) {
                $query = $query->filter(
                    'store', $this->moysklad()->meta()->store($store)->href
                );
            }

            match ($this->settings['archive']) {
                false => $query = $query->filter('archived', false),
                true => $query = $query->filter('archived', true),
                default => [
                    $query = $query->filter('archived', true)
                        ->filter('archived', false)
                ]
            };

            $response = [$query->get()];

            // Если, предположительно, в МойСклад есть ещё остатки, которые
            // можно получить, то вызывает рекурсивно этот же метод.
            // Похоже на получение данных по чанкам
            if (count($response[0]->rows) === 1000) {
                return $this->get($stores, $offset + 1000)->then(
                    fn ($nextResponse) => $deferred->resolve(array_merge(
                        $response, $nextResponse
                    )),
                    fn ($exception) => $deferred->reject($exception)
                );
            }

            $deferred->resolve($response);
        });
    }

    /**
     * Получить краткий отчет об остатках
     *
     * @param string $method Метод для получения остатков (all, bystore)
     * @param array  $stores Хранилища
     *
     * @return PromiseInterface
     */
    protected function getShort(
        string $method = 'all',
        array $stores = []
    ): PromiseInterface {
        return $this->resolveThrow(function ($deferred) use ($method, $stores) {
            $query = $this->query()
                ->report()
                ->method('stock')
                ->method($method)
                ->method('current')
                ->param('stockType', 'quantity');

            if ($this->settings['changedSince']) {
                $query = $query->param(
                    'changedSince', $this->settings['changedSince']
                );
            }

            if ($this->settings['zeroLines'] && !$this->settings['changedSince']) {
                $query = $query->param('include', 'zeroLines');
            }

            foreach (array_splice($stores, 0, min(100, count($stores))) as $store) {
                $query = $query->filter('storeId', $store);
            }

            $response = $query->get();

            if ($stores) {
                return $this->getShort($method, $stores)->then(
                    fn ($nextResponse) => $deferred->resolve(array_merge(
                        $response, $nextResponse
                    )),
                    fn ($exception) => $deferred->reject($exception)
                );
            }

            $deferred->resolve($response);
        });
    }

    /**
     * Получить краткий отчет об остатках по складм
     *
     * @param array $stores Хранилища
     *
     * @return PromiseInterface
     */
    public function getShortByStore(array $stores = []): PromiseInterface
    {
        return $this->getShort('bystore', $stores);
    }

    /**
     * Получить краткий отчет об остатках
     *
     * @return PromiseInterface
     */
    public function getShortAll(): PromiseInterface
    {
        return $this->getShort('all');
    }
}
