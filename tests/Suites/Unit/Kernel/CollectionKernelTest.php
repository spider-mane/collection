<?php

declare(strict_types=1);

namespace Tests\Suites\Unit\Kernel;

use Closure;
use LogicException;
use OutOfBoundsException;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Tests\Support\UnitTestCase;
use WebTheory\Collection\Contracts\CollectionKernelInterface;
use WebTheory\Collection\Contracts\CollectionQueryInterface;
use WebTheory\Collection\Contracts\InvalidOrderExceptionInterface;
use WebTheory\Collection\Kernel\CollectionKernel;
use WebTheory\Collection\Sorting\Order;

class CollectionKernelTest extends UnitTestCase
{
    protected const SUT_CLASS = CollectionKernel::class;

    protected const DUMMY_ITEM_CLASS = 'DummyItem';

    protected const DUMMY_COLLECTION_CLASS = 'DummyCollection';

    protected CollectionKernel $sut;

    protected array $dummyItems;

    protected Closure $dummyGenerator;

    protected string $identifier = 'id';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyItems = $this->createDummyItems();
        $this->dummyGenerator = $this->createDummyFactory();

        $this->sut = new CollectionKernel(
            $this->dummyItems,
            $this->dummyGenerator,
            'id',
        );
    }

    protected function createDummyFactory(): Closure
    {
        return function (CollectionKernel $kernel) {
            $collection = $this->getMockBuilder(stdClass::class)
                ->setMockClassName(static::DUMMY_COLLECTION_CLASS)
                ->addMethods(['getDummyItems'])
                ->getMock();

            $items = $kernel->toArray();

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

    protected function getRandomDummyItem(): MockObject
    {
        return $this->dummyItems[array_rand($this->dummyItems)];
    }

    protected function createDummySortMap(array $keys = null): array
    {
        $keys ??= $this->dummyList(fn () => $this->unique->slug, 20);

        return $this->dummyMap(
            fn () => $this->unique->numberBetween(1, count($keys)),
            $keys
        );
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
            $this->dummyGenerator,
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
            $this->dummyGenerator,
            'id',
            ['id' => 'getDummyId']
        );

        # Act
        $result = $sut->find('id', $itemId);

        # Assert
        $this->assertEquals($item, $result);
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
            $this->dummyGenerator,
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

        $sut = new CollectionKernel($items, $this->dummyGenerator, 'id');

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

    public function orderDataProvider(): array
    {
        return [
            'ascending' => [Order::ASC],
            'descending' => [Order::DESC],
        ];
    }

    /**
     * @test
     */
    public function it_removes_specified_item()
    {
        # Arrange
        $items = $this->createDummyItems();
        $remove = array_rand($items);
        $item = $items[$remove];

        $sut = new CollectionKernel($items, $this->dummyGenerator);

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
        $remove = array_rand($items);
        $item = $items[$remove]->id;

        $sut = new CollectionKernel($items, $this->dummyGenerator, 'id');

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
        $items = $this->createDummyItems();
        $mapped = [];

        foreach ($items as $item) {
            $mapped[$item->id] = $item;
        }

        $remove = array_rand($items);
        $item = $items[$remove]->id;

        $sut = new CollectionKernel($items, $this->dummyGenerator, 'id', [], true);

        # Act
        $sut->remove($item);

        unset($mapped[$item]);

        # Assert
        $this->assertEquals($mapped, $sut->toArray());
    }

    /**
     * @test
     * @dataProvider identifierOperationDataProvider
     */
    public function it_throws_proper_exception_if_an_operation_requiring_an_identifier_is_attempted_without_one(
        array $items,
        string $method,
        array $args,
        string $message = null
    ) {
        $sut = new CollectionKernel($items, $this->dummyGenerator);

        # Expect
        $this->expectException(LogicException::class);

        if (isset($message)) {
            $this->expectExceptionMessage($message);
        }

        # Act
        $this->performSystemAction($sut, $method, $args);
    }

    public function identifierOperationDataProvider(): array
    {
        $this->initFaker();

        $genericMessageTemplate = "Use of " . static::SUT_CLASS . "::%s requires an identifier.";

        $itemCount = 10;
        $itemIds = $this->dummyList(fn () => $this->unique->slug, $itemCount);
        $items = $this->createDummyItems($itemIds);
        $randomItem = $items[array_rand($items)];

        $sortMap = $this->createDummySortMap($itemIds);

        return [
            $this->mut('findById') => [
                'items' => $items,
                'method' => 'findById',
                'args' => [$randomItem->id],
                'message' => sprintf($genericMessageTemplate, 'findById'),
            ],

            $this->mut('sortMapped') => [
                'items' => $items,
                'method' => 'sortMapped',
                'args' => [$sortMap, Order::ASC, null],
                'message' => 'Cannot sort by map without property or item identifier set.',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throws_proper_exception_if_an_item_search_returns_nothing()
    {
        # Expect
        $this->expectException(OutOfBoundsException::class);

        # Act
        $this->sut->findById($this->unique->slug);
    }

    /**
     * @test
     * @dataProvider orderMethodsDataProvider
     */
    public function it_throws_proper_exception_if_an_invalid_order_is_provided(
        string $method,
        array $args,
        string $message = null
    ) {
        # Expect
        $this->expectException(InvalidOrderExceptionInterface::class);

        if (isset($message)) {
            $this->expectExceptionMessage($message);
        }

        # Act
        $this->performSystemAction($this->sut, $method, $args);
    }

    public function orderMethodsDataProvider(): array
    {
        $this->initFaker();

        $invalidOrder = $this->fake->slug(3);

        $sortMap = $this->createDummySortMap();

        return [
            $this->mut('sortBy') => [
                'method' => 'sortBy',
                'args' => ['id', $invalidOrder],
            ],

            $this->mut('sortMapped') => [
                'method' => 'sortMapped',
                'args' => [$sortMap, $invalidOrder, 'id'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider objectMemberAccessabilityDataProvider
     *
     */
    public function it_can_extract_a_column_of_data(bool $memberIsPublic)
    {
        # Arrange
        $ids = $this->dummyList(fn () => $this->unique->slug, 10);
        $items = $this->createDummyItems($ids, $memberIsPublic);

        $sut = new CollectionKernel(
            $items,
            $this->dummyGenerator,
            null,
            ['id' => 'getDummyId']
        );

        # Act
        $results = $sut->column('id');

        # Assert
        $this->assertEquals($ids, $results);
    }

    public function objectMemberAccessabilityDataProvider(): array
    {
        return [
            'direct access' => [true],
            'method access' => [false],
        ];
    }

    /**
     * @test
     */
    public function it_returns_a_concrete_collection_containing_the_difference_between_its_items_and_another()
    {
        # Arrange
        $shared = $this->createDummyItems();
        $items1 = $this->createDummyItems();
        $items2 = $this->createDummyItems();

        $completeItems1 = [...$shared, ...$items1];
        $completeItems2 = [...$shared, ...$items2];

        $difference = [...$items1, ...$items2];

        $sut = new CollectionKernel($completeItems1, $this->dummyGenerator, 'id');

        # Act
        $result = $sut->difference($completeItems2);

        // dd($diff, $result->items);

        # Smoke
        foreach ([$shared, $items1, $items2] as $items) {
            $this->assertNotEmpty($items);
        }

        # Assert
        $this->assertInstanceOf(static::DUMMY_COLLECTION_CLASS, $result);
        $this->assertEquals($difference, $result->items);
    }

    /**
     * @test
     */
    public function it_returns_a_concrete_collection_containing_the_intersection_of_its_items_and_another()
    {
        # Arrange
        $intersection = $this->createDummyItems();

        $items1 = [...$intersection, ...$this->createDummyItems()];
        $items2 = [...$intersection, ...$this->createDummyItems()];

        $sut = new CollectionKernel($items1, $this->dummyGenerator, 'id');

        # Act
        $result = $sut->intersection($items2);

        # Smoke
        foreach ([$intersection, $items1, $items2] as $items) {
            $this->assertNotEmpty($items);
        }

        # Assert
        $this->assertInstanceOf(static::DUMMY_COLLECTION_CLASS, $result);
        $this->assertEquals($intersection, $result->items);
    }

    /**
     * @test
     * @dataProvider collectionComparisonDataProvider
     */
    public function it_determines_whether_or_not_a_passed_collection_matches(bool $matches, array $a, array $b)
    {
        # Arrange
        $sut = new CollectionKernel($a, $this->dummyGenerator, 'id');

        # Act
        $result = $sut->matches($b);

        # Assert
        $this->assertSame($matches, $result);
    }

    public function collectionComparisonDataProvider(): array
    {
        $this->initFaker();

        $items = $this->createDummyItems();

        return [
            'matches' => [true, $items, $items],
            'does not match' => [false, $items, $this->createDummyItems()],
        ];
    }

    /**
     * @test
     */
    public function it_is_traversable()
    {
        # Arrange
        $ids = array_map(fn ($item) => $item->id, $this->sut->toArray());

        # Assert
        foreach ($this->sut as $item) {
            $this->assertInstanceOf(static::DUMMY_ITEM_CLASS, $item);
            $this->assertEquals(current($ids), $item->id);

            next($ids);
        }
    }

    /**
     * @test
     */
    public function it_is_countable()
    {
        $this->assertCount(count($this->sut->toArray()), $this->sut);
    }

    /**
     * @test
     * @dataProvider hasItemsDataProvider
     */
    public function it_accurately_reflects_whether_or_not_it_has_items(bool $hasItems)
    {
        # Arrange
        $items = $hasItems ? $this->createDummyItems() : [];
        $sut = new CollectionKernel($items, $this->dummyGenerator, 'id');

        $this->assertSame($hasItems, $sut->hasItems());
    }

    public function hasItemsDataProvider(): array
    {
        return [
            'has items' => [true],
            'no items' => [false],
        ];
    }

    /**
     * @test
     */
    public function it_removes_items_with_specified_properties()
    {
        # Arrange
        $stripped = $this->dummyList(fn () => $this->unique->slug, 10);
        $remaining = $this->dummyList(fn () => $this->unique->slug, 10);
        $all = [...$stripped, ...$remaining];

        $items = $this->createDummyItems($all);

        $sut = new CollectionKernel($items, $this->dummyGenerator, 'id');

        # Act
        $processed = $sut->whereNotIn('id', $stripped)->items;
        $result = array_map(fn ($item) => $item->id, $processed);

        # Assert
        $this->assertEquals($remaining, $result);
    }

    /**
     * @test
     */
    public function it_merges_collections_without_adding_duplicates()
    {
        # Arrange
        $items1 = $this->createDummyItems();
        $items2 = $this->createDummyItems();

        $shared = $this->createDummyItems();

        $completeItems1 = [...$items1, ...$shared];
        $completeItems2 = [...$items2, ...$shared];

        $expected = [...$items1, ...$shared, ...$items2];

        $sut1 = new CollectionKernel($completeItems1, $this->dummyGenerator);

        # Act
        $result = $sut1->merge($completeItems2);

        # Smoke
        foreach ([$items1, $items2, $expected] as $items) {
            $this->assertNotEmpty($items);
        }

        # Assert
        $this->assertInstanceOf(static::DUMMY_COLLECTION_CLASS, $result);
        $this->assertEquals($expected, $result->items);
    }

    /**
     * @test
     */
    public function it_retrieves_first_item()
    {
        # Arrange
        $items = $this->sut->toArray();

        # Assert
        $this->assertEquals(reset($items), $this->sut->first());
    }

    /**
     * @test
     */
    public function it_retrieves_last_item()
    {
        # Arrange
        $items = $this->sut->toArray();

        # Assert
        $this->assertEquals(end($items), $this->sut->last());
    }

    /**
     * @test
     */
    public function it_retrieves_items_that_meet_criteria()
    {
        # Arrange
        $item = $this->getRandomDummyItem();
        $id = $item->id;

        # Act
        $result = $this->sut->whereEquals('id', $id);

        # Assert
        $this->assertContains($item, $result->items);
    }

    /**
     * @test
     * @dataProvider spawnActionDataProvider
     */
    public function it_spawns_a_concrete_collection_using_a_clone_of_itself(
        array $items,
        string $method,
        array $args
    ) {
        # Arrange
        $spawn = $this->getMockBuilder(stdClass::class)
            ->setMockClassName(static::DUMMY_COLLECTION_CLASS)
            ->addMethods(['getDummyItems'])
            ->getMock();

        $generator = function (CollectionKernel $clone) use ($spawn) {
            $spawn->kernel = $clone;
            $spawn->items = $clone->toArray();

            return $spawn;
        };

        $sut = new CollectionKernel($items, $generator);

        # Act
        $result = $this->performSystemAction($sut, $method, $args);

        # Assert
        $this->assertInstanceOf(static::DUMMY_COLLECTION_CLASS, $result);
        $this->assertNotSame($sut, $result->kernel);
    }

    /**
     * @test
     * @dataProvider spawnActionDataProvider
     */
    public function it_does_not_mutate_original_array_when_spawning(
        array $items,
        string $method,
        array $args
    ) {
        # Arrange
        $count = count($items);

        $sut = new CollectionKernel($items, $this->dummyGenerator);

        # Act
        $result = $this->performSystemAction($sut, $method, $args);

        # Smoke
        $this->assertNotEmpty($items);
        $this->assertNotEmpty($sut->toArray());

        # Assert
        $this->assertSame($items, $sut->toArray());
        $this->assertCount($count, $sut->toArray());
        $this->assertNotEquals($sut->toArray(), $result->items);
    }

    public function spawnActionDataProvider(): array
    {
        $this->initFaker();

        $itemCount = 10;
        $itemIds = $this->dummyList(fn () => $this->unique->slug, $itemCount);
        $items = $this->createDummyItems($itemIds);
        $randomItem = $items[array_rand($items)];
        $itemSubset = $this->dummyList(fn () => $items[array_rand($items)]->id, $itemCount / 2);
        $extraItems = $this->createDummyItems();

        $query = $this->createConfiguredMock(CollectionQueryInterface::class, [
            'query' => [],
        ]);

        $sortMap = $this->dummyMap(
            fn () => $this->unique->numberBetween(1, $itemCount),
            $itemIds
        );

        return [
            $this->mut('query') => [
                'items' => $items,
                'method' => 'query',
                'args' => [$query],
            ],

            $this->mut('where') => [
                'items' => $items,
                'method' => 'where',
                'args' => ['id', '=', $randomItem->id],
            ],

            $this->mut('whereEquals') => [
                'items' => $items,
                'method' => 'whereEquals',
                'args' => ['id', $randomItem->id],
            ],

            $this->mut('whereNotEquals') => [
                'items' => $items,
                'method' => 'whereNotEquals',
                'args' => ['id', $randomItem->id],
            ],

            $this->mut('whereIn') => [
                'items' => $items,
                'method' => 'whereIn',
                'args' => ['id', $itemSubset],
            ],

            $this->mut('whereNotIn') => [
                'items' => $items,
                'method' => 'whereNotIn',
                'args' => ['id', $itemSubset],
            ],

            $this->mut('filter') => [
                'items' => $items,
                'method' => 'filter',
                'args' => [fn () => false],
            ],

            $this->mut('sortBy') => [
                'items' => $items,
                'method' => 'sortBy',
                'args' => ['id'],
            ],

            $this->mut('sortMapped') => [
                'items' => $items,
                'method' => 'sortMapped',
                'args' => [$sortMap, Order::ASC, 'id'],
            ],

            $this->mut('notIn') => [
                'items' => $items,
                'method' => 'notIn',
                'args' => [$items],
            ],

            $this->mut('difference') => [
                'items' => $items,
                'method' => 'difference',
                'args' => [$items],
            ],

            $this->mut('intersection') => [
                'items' => [...$items, ...$extraItems],
                'method' => 'intersection',
                'args' => [$items],
            ],
        ];
    }
}
