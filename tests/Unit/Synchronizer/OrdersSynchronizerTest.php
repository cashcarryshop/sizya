<?php declare(strict_types=1);
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */

namespace CashCarryShop\Sizya\Tests\Unit\Synchronizer;

use CashCarryShop\Sizya\Synchronizer\OrdersSynchronizer;
use CashCarryShop\Sizya\Tests\Synchronizer\MockOrdersSource;
use CashCarryShop\Sizya\Tests\Synchronizer\MockOrdersTarget;
use CashCarryShop\Sizya\Tests\Synchronizer\MockRelationRepository;
use CashCarryShop\Sizya\DTO\RelationDTO;
use CashCarryShop\Sizya\DTO\OrderDTO;
use CashCarryShop\Sizya\DTO\PositionDTO;
use CashCarryShop\Sizya\DTO\AdditionalDTO;
use CashCarryShop\Sizya\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Тестирование класса OrdersSynchronizer.
 *
 * @category UnitTests
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/sizya
 */
#[CoversClass(OrdersSynchronizer::class)]
class OrdersSynchronizerTest extends TestCase
{
    public function testSynchronizeCreateCorrectData(): void
    {
        $source = new MockOrdersSource([
            'statuses' => [
                'process',
                'archive',
                'delivering'
            ]
        ]);

        $target = new MockOrdersTarget([
            'statuses' => [
                'new',
                'archive',
                'delivering'
            ],
            'products' => \array_merge(
                $source->settings['products'], \array_map(
                    fn () => [
                        'id'      => static::guidv4(),
                        'article' => static::fakeArticle()
                    ],
                    \array_fill(0, 50, null)
                )
            ),
            'items' => []
        ]);

        $synchronizer = new OrdersSynchronizer($source, $target);

        $synchronizer->synchronize([
            'throw'      => true,
            'doUpdate'   => false,
            'doCreate'   => true,
            'repository' => new MockRelationRepository([]),
            'status'     => [
                [
                    'source' => 'process',
                    'target' => 'new'
                ],
                [
                    'source' => 'archive',
                    'target' => 'archive'
                ],
                [
                    'source' => 'delivering',
                    'target' => 'delivering'
                ]
            ]
        ]);

        $this->assertData(
            $source->settings['items'],
            $target->settings['items']
        );
    }

    public function testSynchronizeUpdateCorrectData(): void
    {
        $source = new MockOrdersSource([
            'statuses' => [
                'process',
                'archive',
                'delivering'
            ]
        ]);

        $additionalId = $source->settings['additionalsIds'][
            \random_int(
                0,
                \count($source->settings['additionalsIds']) - 1
            )
        ];

        $target = new MockOrdersTarget([
            'statuses' => [
                'new',
                'archive',
                'delivering'
            ],
            'products' => \array_merge(
                $source->settings['products'], \array_map(
                    fn () => [
                        'id'      => static::guidv4(),
                        'article' => static::fakeArticle()
                    ],
                    \array_fill(0, 50, null)
                )
            ),
            'items' => $targets = \array_map(
                function ($item, $idx) use ($additionalId) {
                    $data                   = $item->toArray();
                    $data['id']             = static::guidv4();
                    $data['created']        = static::fakeDtoDate();
                    $data['status']         = 'new';
                    $data['shipmentDate']   = static::fakeDtoDate();
                    $data['deliveringDate'] = static::fakeDtoDate();

                    $data['positions'] = \array_map(
                        function ($position) {
                            $data       = $position->toArray();
                            $data['id'] = static::guidv4();

                            return PositionDTO::fromArray($data);
                        },
                        $data['positions']
                    );

                    if ($idx > 20 && $idx < 40) {
                        $data['externalCode'] = \sha1($data['id']);

                        $data['additionals'] = \array_map(
                            static function ($additional) use ($item, $additionalId) {
                                $data = $additional->toArray();

                                if ($additional->entityId === $additionalId) {
                                    $data['value']    = $item->id;
                                    $data['original'] = $data;
                                }

                                return AdditionalDTO::fromArray($data);
                            },
                            $data['additionals']
                        );
                    }

                    return OrderDTO::fromArray($data);
                },
                $source->settings['items'],
                \array_keys($source->settings['items'])
            )
        ]);

        $onlyForRelations = \array_map(
            static function ($source) {
                $source->externalCode = \sha1($source->created . $source->id);
                return $source;
            },
            \array_slice($source->settings['items'], 0, 20)
        );

        $repository = new MockRelationRepository(
            \array_map(
                static fn ($source, $target) =>
                    RelationDTO::fromArray([
                        'sourceId' => $source->id,
                        'targetId' => $target->id,
                        'testKey'  => $source->testKey
                    ]),
                $onlyForRelations,
                \array_slice($targets, 0, 20)
            )
        );

        $synchronizer = new OrdersSynchronizer($source, $target);

        $synchronizer->synchronize([
            'throw'      => true,
            'doUpdate'   => true,
            'doCreate'   => false,
            'repository' => $repository,
            'additional' => $additionalId,
            'status'     => [
                [
                    'source' => 'process',
                    'target' => 'new'
                ],
                [
                    'source' => 'archive',
                    'target' => 'archive'
                ],
                [
                    'source' => 'delivering',
                    'target' => 'delivering'
                ]
            ]
        ]);

        $this->assertData(
            $source->settings['items'],
            $target->settings['items'],
            $repository->relations,
            $additionalId
        );
    }

    #[Depends('testSynchronizeCreateCorrectData')]
    #[Depends('testSynchronizeUpdateCorrectData')]
    public function testSynchronizeCreateAndUpdateCorrectData(): void
    {
        $source = new MockOrdersSource([
            'statuses' => [
                'process',
                'archive',
                'delivering'
            ]
        ]);

        $additionalId = $source->settings['additionalsIds'][
            \random_int(
                0,
                \count($source->settings['additionalsIds']) - 1
            )
        ];

        $slices = \array_slice($source->settings['items'], 0, 50);

        $target = new MockOrdersTarget([
            'statuses' => [
                'new',
                'archive',
                'delivering'
            ],
            'products' => \array_merge(
                $source->settings['products'], \array_map(
                    fn () => [
                        'id'      => static::guidv4(),
                        'article' => static::fakeArticle()
                    ],
                    \array_fill(0, 50, null)
                )
            ),
            'items' => $targets = \array_map(
                function ($item, $idx) use ($additionalId) {
                    $data                   = $item->toArray();
                    $data['id']             = static::guidv4();
                    $data['created']        = static::fakeDtoDate();
                    $data['status']         = 'new';
                    $data['shipmentDate']   = static::fakeDtoDate();
                    $data['deliveringDate'] = static::fakeDtoDate();

                    $data['positions'] = \array_map(
                        function ($position) {
                            $data       = $position->toArray();
                            $data['id'] = static::guidv4();

                            return PositionDTO::fromArray($data);
                        },
                        $data['positions']
                    );

                    if ($idx > 10 && $idx < 20) {
                        $data['externalCode'] = \sha1($data['id']);

                        $data['additionals'] = \array_map(
                            static function ($additional) use ($item, $additionalId) {
                                $data = $additional->toArray();

                                if ($additional->entityId === $additionalId) {
                                    $data['value']    = $item->id;
                                    $data['original'] = $data;
                                }

                                return AdditionalDTO::fromArray($data);
                            },
                            $data['additionals']
                        );
                    }

                    return OrderDTO::fromArray($data);
                },
                $slices,
                \array_keys($slices)
            )
        ]);

        $onlyForRelations = \array_map(
            static function ($source) {
                $source->externalCode = \sha1($source->created . $source->id);
                return $source;
            },
            \array_slice($source->settings['items'], 0, 10)
        );

        $repository = new MockRelationRepository(
            \array_map(
                static fn ($source, $target) =>
                    RelationDTO::fromArray([
                        'sourceId' => $source->id,
                        'targetId' => $target->id,
                        'testKey'  => $source->testKey
                    ]),
                $onlyForRelations,
                \array_slice($targets, 0, 10)
            )
        );

        $synchronizer = new OrdersSynchronizer($source, $target);

        $synchronizer->synchronize([
            'throw'      => true,
            'doUpdate'   => true,
            'doCreate'   => true,
            'repository' => $repository,
            'additional' => $additionalId,
            'status'     => [
                [
                    'source' => 'process',
                    'target' => 'new'
                ],
                [
                    'source' => 'archive',
                    'target' => 'archive'
                ],
                [
                    'source' => 'delivering',
                    'target' => 'delivering'
                ]
            ]
        ]);

        $this->assertData(
            $source->settings['items'],
            $target->settings['items'],
            $repository->relations,
            $additionalId
        );
    }

    /**
     * Проверить на корректность данные источника и цели.
     *
     * @param array   $sources        Источники
     * @param array   $targets        Цели
     * @param array   $relations      Отношения
     * @param ?string $additionalId Идентификатор доп поля
     *
     * @return void
     */
    protected function assertData(
        array   $sources,
        array   $targets,
        array   $relations    = [],
        ?string $additionalId = null
    ): void {
        $this->assertSameSize($sources, $targets);

        \array_multisort(
            \array_column($sources, 'testKey'),
            SORT_STRING,
            $sources
        );;

        \array_multisort(
            \array_column($targets, 'testKey'),
            SORT_STRING,
            $targets
        );

        $relationsTestKeys = \array_column($relations, 'testKey');

        \reset($targets);
        \reset($relations);
        foreach ($sources as $source) {
            $target   = \current($targets);
            $relation = \current($relations);

            $this->assertEquals(
                $source->testKey,
                $target->testKey,
                'Test key source and target not equals'
            );

            $this->assertEquals(
                $source->created,
                $target->created,
                'Created date source and target not equals'
            );

            if ($source->shipmentDate) {
                $this->assertEquals(
                    $source->shipmentDate,
                    $target->shipmentDate,
                    'Shipment date source and target not equals ' . \key($targets)
                );
            }

            if ($source->deliveringDate) {
                $this->assertEquals(
                    $source->deliveringDate,
                    $target->deliveringDate,
                    'Delivering date source and target not equals ' . \key($targets)
                );
            }

            if (\array_search($source->testKey, $relationsTestKeys) === false) {
                $assert = true;
                if ($additionalId) {
                    foreach ($target->additionals as $additional) {
                        if ($additional->entityId === $additionalId
                            && $additional->value === $source->id
                        ) {
                            $assert = false;
                            break;
                        }
                    }
                }

                if ($assert) {
                    $this->assertEquals(
                        $source->externalCode,
                        $target->externalCode,
                        'External code source and target not equals'
                    );
                }
            }

            $this->assertEquals(
                $source->description,
                $target->description,
                'Description code source and target not equals'
            );

            \array_multisort(
                \array_column($source->positions, 'article'),
                SORT_STRING,
                \array_column($source->positions, 'quantity'),
                \array_column($source->positions, 'price'),
                $source->positions
            );

            \array_multisort(
                \array_column($target->positions, 'article'),
                SORT_STRING,
                \array_column($target->positions, 'quantity'),
                \array_column($target->positions, 'price'),
                $target->positions
            );

            \reset($target->positions);
            foreach ($source->positions as $sourcePosition) {
                $targetPosition = \current($target->positions);

                $this->assertEquals(
                    $sourcePosition->article,
                    $targetPosition->article,
                    'Article source and target not equals'
                );

                $this->assertEquals(
                    $sourcePosition->quantity,
                    $targetPosition->quantity,
                    'Quantity source and target not equals'
                );

                $this->assertEquals(
                    $sourcePosition->discount,
                    $targetPosition->discount,
                    'Discount source and target not equals'
                );

                $this->assertEquals(
                    $sourcePosition->price,
                    $targetPosition->price,
                    'Price source and target not equals'
                );

                $this->assertEquals(
                    $sourcePosition->currency,
                    $targetPosition->currency,
                    'Currency source and target not equals'
                );

                $this->assertEquals(
                    $sourcePosition->vat,
                    $targetPosition->vat,
                    'Vat source and target not equals'
                );

                \next($target->positions);
            }

            \next($targets);
        }
    }
}
