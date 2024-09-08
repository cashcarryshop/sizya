<?php
/**
 * Этот файл является частью пакета sizya
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

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Абстрактный класс для заказов покупателей МойСклад
 *
 * Содержит правила валидации для настроек, необходимых
 * для работы с заказами покупателей МойСклад, как
 * для источника, так и для цели.
 *
 * @category Moysklad
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
abstract class CustomerOrders extends AbstractSource
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
            'organization' => null,
            'agent'        => null,
            'project'      => null,
            'contract'     => null,
            'salesChannel' => null,
            'store'        => null,
            'limit'        => 100,
            'order'        => [['created', 'desc']],
            'vatEnabled'   => false,
            'vatIncluded'  => false,
            'products'     => null
        ];

        parent::__construct(array_replace($defaults, $settings));

        $this->settings['products'] = $this->getSettings(
            'products', new Products([
                'credentials' => $this->getCredentials(),
                'client'      => $this->getSettings('client')
            ])
        );;
    }

    /**
     * Правила валидации для настроек
     *
     * @return array
     */
    protected function rules(): array
    {
        return array_merge(
            parent::rules(), [
                'organization' => [
                    new Assert\Type('string', 'null'),
                    ...$this instanceof SynchronizerTargetInterface
                        ? [
                            new Assert\NotBlank,
                            new Assert\Length(36, 36)
                        ] : []
                ],
                'agent' => [
                    new Assert\Type(['string', 'null']),
                    ...$this instanceof SynchronizerTargetInterface
                        ? [
                            new Assert\NotBlank,
                            new Assert\Length(36, 36)
                        ] : []
                ],
                'project' => [
                    new Assert\Type(['string', 'null']),
                    new Assert\When(
                        expression: 'value !== null',
                        constraints: [new Assert\Length(36, 36)]
                    )
                ],
                'contract' => [
                    new Assert\Type(['string', 'null']),
                    new Assert\When(
                        expression: 'value !== null',
                        constraints: [new Assert\Length(36, 36)]
                    )
                ],
                'salesChannel' => [
                    new Assert\Type(['string', 'null']),
                    new Assert\When(
                        expression: 'value !== null',
                        constraints: [new Assert\Length(36, 36)]
                    )
                ],
                'store' => [
                    new Assert\Type(['string', 'null']),
                    new Assert\When(
                        expression: 'value !== null',
                        constraints: [new Assert\Length(36, 36)]
                    )
                ],
                'limit' => [
                    new Assert\Type('int'),
                    new Assert\Range(min: 100)
                ],
                'order' => new Assert\Collection([
                    0 => [
                        new Assert\Type('string'),
                        new Assert\Choice([
                            'created',
                            'deliveryPlannedMoment',
                            'name',
                            'id',
                            'deleted',
                            'sum'
                        ])
                    ],
                    1 => [
                        new Assert\Type('string'),
                        new Assert\Choice(['asc', 'desc'])
                    ]
                ]),
                'products'    => [new Assert\Type([null, Products::class])],
                'vatEnabled'  => [new Assert\Type('bool')],
                'vatIncluded' => [new Assert\Type('bool')]
            ]
        );
    }
}
