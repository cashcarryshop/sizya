<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
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
 * Абстрактный класс для заказов покупателей МойСклад.
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
     * Объект для получения товаров МойСклад.
     *
     * @var Products
     */
    protected Products $products;

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
            'organization'     => null,
            'agent'            => null,
            'project'          => null,
            'contract'         => null,
            'salesChannel'     => null,
            'store'            => null,
            'limit'            => 100,
            'order'            => [['created', 'desc']],
            'vatEnabled'       => false,
            'vatIncluded'      => false,
            'variantsIncludes' => true
        ];

        parent::__construct(\array_replace($defaults, $settings));

        $this->products = new Products([
            'credentials'      => $this->getSettings('credentials'),
            'client'           => $this->getSettings('client'),
            'variantsIncludes' => $this->getSettings('variantsIncludes')
        ]);
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
                'organization' => [
                    new Assert\Type('string', 'null'),
                    ...$this instanceof SynchronizerTargetInterface
                        ? [
                            new Assert\NotBlank,
                            new Assert\Uuid(strict: false)
                        ] : []
                ],
                'agent' => [
                    new Assert\Type(['string', 'null']),
                    ...$this instanceof SynchronizerTargetInterface
                        ? [
                            new Assert\NotBlank,
                            new Assert\Uuid(strict: false)
                        ] : []
                ],
                'project' => [
                    new Assert\Type(['string', 'null']),
                    new Assert\When(
                        expression: 'value !== null',
                        constraints: [new Assert\Uuid(strict: false)]
                    )
                ],
                'contract' => [
                    new Assert\Type(['string', 'null']),
                    new Assert\When(
                        expression: 'value !== null',
                        constraints: [new Assert\Uuid(strict: false)]
                    )
                ],
                'salesChannel' => [
                    new Assert\Type(['string', 'null']),
                    new Assert\When(
                        expression: 'value !== null',
                        constraints: [new Assert\Uuid(strict: false)]
                    )
                ],
                'store' => [
                    new Assert\Type(['string', 'null']),
                    new Assert\When(
                        expression: 'value !== null',
                        constraints: [new Assert\Uuid(strict: false)]
                    )
                ],
                'limit' => [
                    new Assert\Type('int'),
                    new Assert\Range(min: 1)
                ],
                'order' => new Assert\All(
                    new Assert\Collection([
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
                    ])
                ),
                'vatEnabled'       => [new Assert\Type('bool')],
                'vatIncluded'      => [new Assert\Type('bool')],
                'variantsIncludes' => [new Assert\Type('bool')]
            ]
        );
    }
}
