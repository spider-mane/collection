<?php

declare(strict_types=1);

namespace Tests\Suites\Unit\Kernel;

use Closure;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Tests\Support\UnitTestCase;
use WebTheory\Collection\Contracts\CollectionKernelInterface;
use WebTheory\Collection\Kernel\CollectionKernel;
use WebTheory\Collection\Sorting\Order;

class CollectionKernelTest extends UnitTestCase
{
    protected CollectionKernel $sut;

    protected array $dummyItems;

    protected Closure $dummyFactory;

    protected string $identifier = 'id';

    protected const DUMMY_ITEM_CLASS = 'DummyItem';

    protected const DUMMY_COLLECTION_CLASS = 'DummyCollection';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyItems = $this->createDummyItems();
        $this->dummyFactory = $this->createDummyFactory();

        $this->sut = new CollectionKernel(
            $this->dummyItems,
            $this->dummyFactory,
            'id',
        );
    }

    protected function createDummyFactory(): Closure
    {
        return function (...$items) {
            $collection = $this->getMockBuilder(stdClass::class)
                ->setMockClassName(static::DUMMY_COLLECTION_CLASS)
                ->addMethods(['getDummyItems'])
                ->getMock();

            $collection->items = $items;

            $collection->method('getDummyItems')->willReturn($items);

            return $collection;
        };
    }

    protected function createDummyItems(array $identifiers = [], bool $idProp = true): array
    {
        return array_map(
            fn ($identifier) => $this->createDummyItem($identifier, $idProp),
            $identifiers ?: $this->dummyList(fn () => $this->unique->slug, 20)
        );
    }

    protected function createDummyItem($identifier, bool $idProp = true): MockObject
    {
        $item = $this->getMockBuilder(stdClass::class)
            ->setMockClassName(static::DUMMY_ITEM_CLASS)
            ->addMethods(['getDummyId'])
            ->getMock();

        $item->propertyToEnsureComparability = $this->unique->sentence;

        $item->method('getDummyId')->willReturn($identifier);

        if ($idProp) {
            $item->id = $identifier;
        }

        return $item;
    }

    /**
     * @test
     */
    public function it_is_instance_of_CollectionKernelInterface()
    {
        $this->assertInstanceOf(CollectionKernelInterface::class, $this->sut);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_identifier_cannot_be_accessed()
    {
        # Arrange
        $identifier = $this->unique->slug;
        $class = static::DUMMY_ITEM_CLASS;
        $message = "No method of access for \"{$identifier}\" in {$class} has been defined.";

        # Expect
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage($message);

        # Act
        new CollectionKernel(
            $this->dummyItems,
            $this->dummyFactory,
            $identifier
        );
    }

    /**
     * @test
     */
    public function it_returns_all_items_passed_to_it()
    {
        # Smoke
        $this->assertNotEmpty($this->dummyItems);

        # Assert
        $this->assertEquals($this->dummyItems, $this->sut->toArray());
    }

    /**
     * @test
     */
    public function it_can_access_properties_via_mapped_accessor_methods()
    {
        # Arrange
        $items = $this->createDummyItems([], false);
        $item = $items[0];
        $itemId = $item->getDummyId();

        $sut = new CollectionKernel(
            $items,
            $this->dummyFactory,
            'id',
            ['id' => 'getDummyId']
        );

        # Act
        $result = $sut->find('id', $itemId);

        # Assert
        $this->assertEquals($item, $result);
    }

    public function orderDataProvider(): array
    {
        return [
            'ascending' => [Order::ASC],
            'descending' =>  [Order::DESC],
        ];
    }

    /**
     * @test
     * @dataProvider orderDataProvider
     */
    public function it_can_sort_items_according_to_property_value(string $order)
    {
        # Arrange
        $properties = $this->dummyList(fn () => $this->unique->slug, 20);
        $items = $this->createDummyItems($properties);

        $sut = new CollectionKernel(
            $items,
            $this->dummyFactory,
            'id',
        );

        # Act
        $sorted = $sut->sortBy('id', $order)->items;
        $results = array_map(
            fn ($item) => $item->id,
            $sorted
        );

        Order::DESC === $order
            ? rsort($properties)
            : sort($properties);

        # Smoke
        $this->assertNotEquals($items, $sorted);

        # Assert
        $this->assertEquals($properties, $results);
    }

    /**
     * @test
     * @dataProvider orderDataProvider
     */
    public function it_sorts_items_by_property_according_to_provided_map(string $order)
    {
        # Arrange
        $count = 50;
        $properties = $this->dummyList(fn () => $this->unique->slug, $count);
        $items = $this->createDummyItems($properties);
        $map = $this->dummyMap(
            fn () => $this->unique->numberBetween(1, $count),
            $properties
        );

        $sut = new CollectionKernel($items, $this->dummyFactory, 'id');

        # Act
        $mapped = $sut->sortMapped($map, $order, 'id')->items;
        $results = array_map(fn ($item) => $item->id, $mapped);

        Order::DESC === $order
            ? arsort($map)
            : asort($map);

        $expectedOrder = array_keys($map);

        # Smoke
        $this->assertNotEquals($items, $mapped);

        # Assert
        $this->assertEquals($expectedOrder, $results);
    }

    /**
     * @test
     */
    public function it_removes_specified_item()
    {
        # Arrange
        $items = $this->createDummyItems();
        $remove = rand(0, count($items) - 1);
        $item = $items[$remove];

        $sut = new CollectionKernel($items, $this->dummyFactory);

        # Act
        $sut->remove($item);
        unset($items[$remove]);

        # Assert
        $this->assertEquals($items, $sut->toArray());
    }

    /**
     * @test
     */
    public function it_removes_specified_item_by_identifier_when_collection_is_not_mapped()
    {
        # Arrange
        $items = $this->createDummyItems();
        $remove = rand(0, count($items) - 1);
        $item = $items[$remove]->id;

        $sut = new CollectionKernel($items, $this->dummyFactory, 'id');

        # Act
        $sut->remove($item);

        unset($items[$remove]);

        # Assert
        $this->assertEquals($items, $sut->toArray());
    }

    /**
     * @test
     */
    public function it_removes_specified_item_by_identifier_when_collection_is_mapped()
    {
        # Arrange
        $ids = $this->dummyList(fn () => $this->unique->slug, 5);
        $items = $this->createDummyItems($ids);
        $mapped = [];

        foreach ($items as $item) {
            $mapped[$item->id] = $item;
        }

        $remove = rand(0, count($items) - 1);
        $item = $items[$remove]->getDummyId();

        $sut = new CollectionKernel($items, $this->dummyFactory, 'id', [], true);

        # Act
        $sut->remove($item);

        unset($mapped[$item]);

        # Assert
        $this->assertEquals($mapped, $sut->toArray());
    }
}
