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

use CashCarryShop\Sizya\Http\Utils;
use GuzzleHttp\Promise\PromiseInterface;
use Respect\Validation\Validator as v;
use Throwable;

/**
 * Класс с настройками и логикой получения
 * остатков Moysklad
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
final class Assortment extends AbstractEntity
{
    /**
     * Получить ассортимент по артикулам
     *
     * @param array $articles Артикулы
     *
     * @return PromiseInterface
     */
    public function getByArticles(array $articles): PromiseInterface
    {
        v::length(1)->each(v::stringType())->assert($articles);

        $builder = $this->builder()
            ->point('entity/assortment')
            ->filter('archived', false)
            ->filter('archived', true);

        $promises = [];
        foreach (array_chunk($articles, 80) as $chunk) {
            $clone = clone $builder;

            foreach ($chunk as $article) {
                $clone->filter('article', $article);
            }

            $promises[] = $this->pool()->add($clone->build('GET'));
        }

        return $this->getPromiseAggregator()->settle($promises);
    }

    /**
     * Получить ассортимент по артикулу
     *
     * @param string $article Артикул
     *
     * @return PromiseInterface
     */
    public function getByArticle(string $article): PromiseInterface
    {
        return Utils::unwrapSingleSettle($this->getByArticles([$article]))->then(
            static fn ($response) =>  $response->withBody(
                Utils::getJsonStream(
                    $response->getBody()->toArray()['rows'][0] ?? []
                )
            )
        );
    }

    /**
     * Получить ассортимент по кодам
     *
     * @param array $codes Коды товаров
     *
     * @return PromiseInterface
     */
    public function getByCodes(array $codes): PromiseInterface
    {
        v::length(1)->each(v::stringType())->assert($codes);

        $builder = $this->builder()
            ->point('entity/assortment')
            ->filter('archived', false)
            ->filter('archived', true);

        $promises = [];
        foreach (array_chunk($codes, 80) as $chunk) {
            $clone = clone $builder;

            foreach ($chunk as $code) {
                $clone->filter('code', $code);
            }

            $promises[] = $this->pool()->add($clone->build('GET'));
        }

        return $this->getPromiseAggregator()->settle($promises);
    }

    /**
     * Получить ассортимент по коду
     *
     * @param string $code Код
     *
     * @return PromiseInterface
     */
    public function getByCode(string $code): PromiseInterface
    {
        return Utils::unwrapSingleSettle($this->getByCodes([$code]))->then(
            static fn ($response) => $response->withBody(
                Utils::getJsonStream(
                    $response->getBody()->toArray()['rows'][0] ?? []
                )
            )
        );
    }
}
