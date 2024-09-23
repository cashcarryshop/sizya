<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya
 *
 * PHP version 8
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Ozon;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Абстрактный класс для заказов покупателей МойСклад
 *
 * Содержит правила валидации для настроек, необходимых
 * для работы с остатками Ozon, как
 * для источника, так и для цели.
 *
 * @category Ozon
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class AbstractStocks extends AbstractSource
{
    /**
     * Создать экземпляр класса для
     * работы с заказами покупателей
     * МойСклад
     *
     * @param array $settings Настройки
     */
    public function __construct(array $settings)
    {
        $defaults = [
            'limit'    => 100,
            'products' => null
        ];

        parent::__construct(\array_replace($defaults, $settings));

        if (is_null($this->getSettings('products'))) {
            $this->settings['products'] = new Products([
                'limit'    => $this->getSettings('limit'),
                'token'    => $this->getSettings('token'),
                'clientId' => $this->getSettings('clientId'),
                'client'   => $this->getSettings('client')
            ]);
        }
    }

    /**
     * Правила валидации для настроек
     *
     * @return array
     */
    protected function rules(): array
    {
        return \array_merge(
            parent::rules(), [
                'limit' => [
                    new Assert\Type('int'),
                    new Assert\Range(min: 100)
                ],
                'products' => [new Assert\Type([null, Products::class])],
            ]
        );
    }
}
