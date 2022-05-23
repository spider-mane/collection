<?php

declare(strict_types=1);

namespace Tests\Suites\Unit\Kernel;

use Closure;
use LogicException;
use stdClass;
use Tests\Support\Dummies\DummyItem;
use Tests\Support\UnitTestCase;
use WebTheory\Collection\Contracts\CollectionKernelInterface;
use WebTheory\Collection\Contracts\CollectionQueryInterface;
use WebTheory\Collection\Contracts\InvalidOrderExceptionInterface;
use WebTheory\Collection\Enum\Order;
use WebTheory\Collection\Kernel\Builder\CollectionKernelBuilder;
use WebTheory\Collection\Kernel\CollectionKernel;

class CollectionKernelTest extends UnitTestCase
{
    protected const SUT_CLASS = CollectionKernel::class;

    protected const DUMMY_ITEM_CLASS = DummyItem::class;

    protected const DUMMY_COLLECTION_CLASS = 'DummyCollection';

    protected CollectionKernel $sut;

    protected array $dummyItems;

    protected Closure $dummyGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyItems = $this->createDummyItems();
        $this->dummyGenerator = $this->createDummyGenerator();

        $this->sut = new CollectionKernel(
            $this->dummyItems,
            $this->dummyGenerator,
            'id',
        );
    }

    public function buildCollectionKernel(bool $isMap = false): CollectionKernelBuilder
    {
        $builder = (new CollectionKernelBuilder())
            ->withItems($this->dummyItems)
            ->withGenerator($this->dummyGenerator);

        $isMap ? $builder->thatIsMapped() : $builder->thatIsNotMapped();

        return $builder;
    }

    protected function createDummyGenerator(): Closure
    {
        return function (CollectionKernel $kernel) {
            $collection = $this->getMockBuilder(stdClass::class)
                ->setMockClassName(static::DUMMY_COLLECTION_CLASS)
                ->addMethods(['getItems'])
                ->getMock();

            $items = $kernel->toArray();

            $collection->items = $items;

            $collection->method('getItems')->willReturn($items);

            return $collection;
        };
    }

    protected function createDummyIds(int $count): array
    {
        return $this->dummyList(fn () => $this->unique->slug, $count);
    }

    protected function createDummyItems(array $identifiers = [], bool $idProp = true): array
    {
        return array_map(
            fn ($identifier) => $this->createDummyItem($identifier, $idProp),
            $identifiers ?: $this->dummyList(fn () => $this->unique->slug, 20)
        );
    }

    protected function createDummyItemMap(int $count = 10): array
    {
        $map = [];

        for ($i = 0; $i < $count; $i++) {
            $id = $this->unique->slug;

            $map[$id] = $this->createDummyItem($id);
        }

        return $map;
    }

    protected function createDummyItemMapWithKeys(array $keys): array
    {
        $map = [];

        foreach ($keys as $key) {
            $map[$key] = $this->createDummyItem($key);
        }

        return $map;
    }

    protected function createDummyItem($identifier, bool $idProp = true): DummyItem
    {
        $item = new DummyItem($identifier);

        if ($idProp) {
            $item->id = $identifier;
        }

        return $item;
    }

    protected function getRandomDummyItem(): DummyItem
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

    protected function sortArray(array $array, string $order): array
    {
        switch ($order) {
            case Order::Asc:
                sort($array);

                break;
            case Order::Desc:
                rsort($array);

                break;
        }

        return $array;
    }

    protected function sortArrayAndMaintainKeys(array $array, string $order): array
    {
        switch ($order) {
            case Order::Asc:
                asort($array);

                break;
            case Order::Desc:
                arsort($array);

                break;
        }

        return $array;
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
        $class = addslashes(static::DUMMY_ITEM_CLASS);

        # Expect
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches("/{$identifier}/");
        $this->expectExceptionMessageMatches("/{$class}/");

        # Act
        $this->buildCollectionKernel()->withIdentifier($identifier)->build();
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
    public function it_accesses_properties_via_mapped_accessor_methods()
    {
        # Arrange
        $items = $this->createDummyItems([], false);
        $item = $items[0];
        $itemId = $item->getId();

        $sut = new CollectionKernel(
            $items,
            $this->dummyGenerator,
            'id',
            ['id' => 'getId']
        );

        # Act
        $result = $sut->firstWhere('id', '=', $itemId);

        # Assert
        $this->assertEquals($item, $result);
    }

    /**
     * @test
     */
    public function it_accesses_properties_by_inference_if_an_accessor_has_not_been_specified()
    {
        # Arrange
        $items = $this->createDummyItems([], false);
        $item = $items[0];
        $itemId = $item->getId();

        $sut = $this->buildCollectionKernel()->withItems($items)->build();

        # Act
        $result = $sut->firstWhere('id', '=', $itemId);

        # Assert
        $this->assertEquals($item, $result);
    }

    /**
     * @test
     * @dataProvider orderDataProvider
     */
    public function it_sorts_items_according_to_property_value(string $order, bool $identifier, bool $isMap)
    {
        # Arrange
        $properties = $this->dummyList(fn () => $this->unique->slug, 20);
        $items = ($isMap && !$identifier)
            ? $this->createDummyItemMapWithKeys($properties)
            : $this->createDummyItems($properties);

        $sut = $this->buildCollectionKernel()
            ->withItems($items)
            ->withIdentifier($identifier ? 'id' : null)
            ->withMapped($isMap)
            ->build();

        # Act
        $sorted = $sut->sortBy('id', $order)->items;
        $results = $isMap
            ? array_keys($sorted)
            : array_map(fn ($item) => $item->id, $sorted);

        $properties = $this->sortArray($properties, $order);

        # Smoke
        $this->assertNotSame($items, $sorted);

        # Assert
        $this->assertEquals($properties, $results);
    }

    /**
     * @test
     * @dataProvider orderDataProvider
     */
    public function it_sorts_items_by_property_according_to_provided_map(string $order, bool $identifier, bool $isMap)
    {
        # Arrange
        $properties = $this->dummyList(fn () => $this->unique->slug, 20);

        $items = ($isMap && !$identifier)
            ? $this->createDummyItemMapWithKeys($properties)
            : $this->createDummyItems($properties);

        $map = $this->createDummySortMap($properties);

        $sut = $this->buildCollectionKernel()
            ->withItems($items)
            ->withIdentifier($identifier ? 'id' : null)
            ->withMapped($isMap)
            ->build();

        # Act
        $sorted = $sut->sortMapped($map, 'id', $order)->items;
        $results = $isMap
            ? array_keys($sorted)
            : array_map(fn ($item) => $item->id, $sorted);

        $expectedOrder = array_keys($this->sortArrayAndMaintainKeys($map, $order));

        # Smoke
        $this->assertNotSame($items, $sorted);

        # Assert
        $this->assertEquals($expectedOrder, $results);
    }

    public function orderDataProvider(): array
    {
        return [
            'order=asc, driver=identifiable-item list' => [
                'order' => Order::Asc,
                'identifier' => true,
                'mapped' => false,
            ],
            'order=desc, driver=identifiable-item list' => [
                'order' => Order::Desc,
                'identifier' => true,
                'mapped' => false,
            ],

            'order=asc, driver=property map' => [
                'order' => Order::Asc,
                'identifier' => true,
                'mapped' => true,
            ],
            'order=desc, driver=property map' => [
                'order' => Order::Desc,
                'identifier' => true,
                'mapped' => true,
            ],

            'order=asc, driver=standard list' => [
                'order' => Order::Asc,
                'identifier' => false,
                'mapped' => false,
            ],
            'order=desc, driver=standard list' => [
                'order' => Order::Desc,
                'identifier' => false,
                'mapped' => false,
            ],

            'order=asc, driver=standard map' => [
                'order' => Order::Asc,
                'identifier' => false,
                'mapped' => true,
            ],
            'order=desc, driver=standard map' => [
                'order' => Order::Desc,
                'identifier' => false,
                'mapped' => true,
            ],
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
                'args' => [$sortMap, 'id', $invalidOrder],
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
            ['id' => 'getId']
        );

        # Act
        $results = $sut->column('id');

        # Assert
        $this->assertEquals($ids, $results);
    }

    public function objectMemberAccessabilityDataProvider(): array
    {
        return [
            'access=member' => [true],
            'access=method' => [false],
        ];
    }

    /**
     * @test
     */
    public function it_returns_a_concrete_collection_containing_the_contrast_between_its_items_and_another()
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
        $result = $sut->contrast($completeItems2);

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
        $result = $sut->intersect($items2);

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
            'match=true' => [true, $items, $items],
            'match=false' => [false, $items, $this->createDummyItems()],
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
            'status=true' => [true],
            'status=false' => [false],
        ];
    }

    /**
     * @test
     */
    public function it_returns_a_collection_without_items_with_specified_properties()
    {
        # Arrange
        $stripped = $this->dummyList(fn () => $this->unique->slug, 10);
        $remaining = $this->dummyList(fn () => $this->unique->slug, 10);
        $all = [...$stripped, ...$remaining];

        $items = $this->createDummyItems($all);

        $sut = new CollectionKernel($items, $this->dummyGenerator, 'id');

        # Act
        $processed = $sut->where('id', 'not in', $stripped)->items;
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
        $result = $this->sut->where('id', '=', $id);

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
            ->addMethods(['getItems'])
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
        $itemSubset = $this->dummyList(fn () => $items[array_rand($items)], $itemCount / 2);
        $itemSubsetIds = $this->dummyList(fn () => $items[array_rand($items)]->id, $itemCount / 2);
        $extraItems = $this->createDummyItems();

        $query = $this->createConfiguredMock(CollectionQueryInterface::class, [
            'query' => $itemSubset,
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

            $this->mut('filter') => [
                'items' => $items,
                'method' => 'filter',
                'args' => [fn ($item) => in_array($item, $itemSubset)],
            ],

            $this->mut('sortBy') => [
                'items' => $items,
                'method' => 'sortBy',
                'args' => ['id'],
            ],

            $this->mut('sortMapped') => [
                'items' => $items,
                'method' => 'sortMapped',
                'args' => [$sortMap, 'id'],
            ],

            $this->mut('diff') => [
                'items' => $items,
                'method' => 'diff',
                'args' => [$itemSubset],
            ],

            $this->mut('contrast') => [
                'items' => [...$items, ...$extraItems],
                'method' => 'contrast',
                'args' => [$items],
            ],

            $this->mut('intersect') => [
                'items' => [...$items, ...$extraItems],
                'method' => 'intersect',
                'args' => [$items],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider spawnActionStorageDataProvider
     */
    public function it_maintains_storage_schema_when_spawning(
        array $items,
        string $method,
        array $args,
        bool $mapped
    ) {
        # Arrange
        $sut = $this->buildCollectionKernel($mapped)
            ->withItems($items)
            ->withIdentifier('id')
            ->build();

        $sutArray = $sut->toArray();

        # Act
        $result = $this->performSystemAction($sut, $method, $args);

        # Smoke
        $this->assertNotEmpty($result->items);

        # Assert
        if ($mapped) {
            $this->assertFalse(array_is_list($result->items), 'Failed asserting array is not a list');

            foreach ($result->items as $key => $item) {
                if (in_array($item, $sutArray)) {
                    $this->assertSame($item, $sutArray[$key]);
                    $this->assertSame($key, array_search($item, $sutArray));
                }
            }
        } else {
            $this->assertTrue(array_is_list($result->items), 'Failed asserting array is a list');
        }
    }

    public function spawnActionStorageDataProvider()
    {
        $methods = $this->spawnActionDataProvider();
        $strategies = ['map', 'list'];

        $data = [];

        foreach ($methods as $method => $args) {
            foreach ($strategies as $strategy) {
                $args['mapped'] = $strategy === 'map' ? true : false;

                $data["$method, schema=$strategy"] = $args;
            }
        }

        return $data;
    }

    /**
     * @test
     * @dataProvider iterationDataProvider
     */
    public function it_iterates_over_each_item_in_the_collection_using_provided_callback(string $method)
    {
        # Arrange
        $mockMethod = $this->fake->word;
        $callback = fn ($item) => $item->$mockMethod();
        $items = $this->dummyList(
            fn () => $this->getMockBuilder(DummyItem::class)
                ->setConstructorArgs([$this->unique->slug])
                ->addMethods([$mockMethod])
                ->getMock(),
            5
        );

        $sut = $this->buildCollectionKernel()
            ->withItems($items)
            ->build();

        # Expect
        foreach ($items as $item) {
            $item->expects($this->once())->method($mockMethod);
        }

        # Act
        $this->performSystemAction($sut, $method, [$callback]);
    }

    public function iterationDataProvider(): array
    {
        return [
            $this->mut('map') => ['map'],
            $this->mut('walk') => ['walk'],
            $this->mut('foreach') => ['foreach'],
        ];
    }

    /**
     * @test
     */
    public function it_serializes_items_as_json()
    {
        $this->assertSame(
            json_encode($this->dummyItems, JSON_THROW_ON_ERROR),
            $this->sut->toJson()
        );
    }

    /**
     * @test
     */
    public function it_is_json_serializable()
    {
        $this->assertSame(
            json_encode($this->dummyItems, JSON_THROW_ON_ERROR),
            json_encode($this->sut, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @test
     */
    public function it_is_serialized_as_the_stored_array()
    {
        $this->assertSame($this->dummyItems, $this->sut->__serialize());
    }

    /**
     * @test
     * @dataProvider containsDataProvider
     */
    public function it_accurately_reports_whether_or_not_it_contains_an_item(
        array $items,
        $seek,
        bool $identifier,
        bool $mapped,
        bool $contained
    ) {
        # Arrange
        $sut = $this->buildCollectionKernel()
            ->withItems($items)
            ->withIdentifier($identifier ? 'id' : null)
            ->withMapped($mapped)
            ->build();

        # Act
        $result = $sut->contains($seek);

        # Assert
        $contained ? $this->assertTrue($result) : $this->assertFalse($result);
    }

    public function containsDataProvider(): array
    {
        $this->initFaker();

        $count = 5;
        $ids = $this->dummyList(fn () => $this->unique->slug, $count);
        $items = $this->createDummyItems($ids);
        $randomItem = $items[array_rand($items)];
        $randomId = $randomItem->id;

        $map = $this->dummyMap(
            fn () => $this->createDummyItem($this->unique->slug),
            $ids
        );

        return [
            'status=true, driver=property map' => [
                'items' => $items,
                'seek' => $randomId,
                'identifier' => true,
                'mapped' => true,
                'contained' => true,
            ],
            'status=false, driver=property map' => [
                'items' => $items,
                'seek' => $this->unique->slug,
                'identifier' => true,
                'mapped' => true,
                'contained' => false,
            ],
            'status=true, driver=identifiable-item list' => [
                'items' => $items,
                'seek' => $randomId,
                'identifier' => true,
                'mapped' => false,
                'contained' => true,
            ],
            'status=false, driver=identifiable-item list' => [
                'items' => $items,
                'seek' => $this->unique->slug,
                'identifier' => true,
                'mapped' => false,
                'contained' => false,
            ],
            'status=true, driver=standard list' => [
                'items' => $items,
                'seek' => $randomItem,
                'identifier' => false,
                'mapped' => false,
                'contained' => true,
            ],
            'status=false, driver=standard list' => [
                'items' => $items,
                'seek' => $this->createDummyItem($this->fake->slug),
                'identifier' => false,
                'mapped' => false,
                'contained' => false,
            ],
            'status=true, driver=standard map' => [
                'items' => $map,
                'seek' => $randomId,
                'identifier' => false,
                'mapped' => true,
                'contained' => true,
            ],
            'status=false, driver=standard map' => [
                'items' => $map,
                'seek' => $this->unique->slug,
                'identifier' => false,
                'mapped' => true,
                'contained' => false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sortedMapDataProvider
     */
    public function it_maintains_key_associations_when_performing_a_sorting_operation_on_a_map(
        array $items,
        ?string $identifier,
        string $method,
        array $args
    ) {
        # Arrange
        $sut = $this->buildCollectionKernel()
            ->withItems($items)
            ->withMapped(true)
            ->withIdentifier($identifier)
            ->build();

        # Act
        $result = $this->performSystemAction($sut, $method, $args)->items;
        ksort($items);
        ksort($result);

        # Assert
        $this->assertSame($items, $result);
    }

    public function sortedMapDataProvider(): array
    {
        $this->initFaker();

        $ids = $this->createDummyIds(10);
        $sort = $this->createDummySortMap($ids);
        $items = $this->createDummyItemMapWithKeys($ids);

        return [
            // SortBy
            $this->mut('SortBy', 'mapping=auto, order=ascending') => [
                'items' => $items,
                'identifier' => 'id',
                'method' => 'SortBy',
                'args' => ['id', Order::Asc],
            ],
            $this->mut('SortBy', 'mapping=auto, order=descending') => [
                'items' => $items,
                'identifier' => 'id',
                'method' => 'SortBy',
                'args' => ['id', Order::Desc],
            ],
            $this->mut('SortBy', 'mapping=standard, order=ascending') => [
                'items' => $items,
                'identifier' => null,
                'method' => 'SortBy',
                'args' => ['id', Order::Asc],
            ],
            $this->mut('SortBy', 'mapping=standard, order=descending') => [
                'items' => $items,
                'identifier' => null,
                'method' => 'SortBy',
                'args' => ['id', Order::Desc],
            ],

            // SortMapped
            $this->mut('sortMapped', 'mapping=auto, order=ascending') => [
                'items' => $items,
                'identifier' => 'id',
                'method' => 'sortMapped',
                'args' => [$sort, 'id', Order::Asc],
            ],
            $this->mut('sortMapped', 'mapping=auto, order=descending') => [
                'items' => $items,
                'identifier' => 'id',
                'method' => 'sortMapped',
                'args' => [$sort, 'id', Order::Desc],
            ],
            $this->mut('sortMapped', 'mapping=standard, order=ascending') => [
                'items' => $items,
                'identifier' => null,
                'method' => 'sortMapped',
                'args' => [$sort, 'id', Order::Asc],
            ],
            $this->mut('sortMapped', 'mapping=standard, order=descending') => [
                'items' => $items,
                'identifier' => null,
                'method' => 'sortMapped',
                'args' => [$sort, 'id', Order::Desc],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider mutatedMapDataProvider
     */
    public function it_maintains_key_associations_when_performing_a_fusion_operation_on_a_map(
        array $items,
        bool $identifier,
        string $method,
        array $args
    ) {
        # Arrange
        $sut = $this->buildCollectionKernel()
            ->withItems($items)
            ->withMapped(true)
            ->withIdentifier($identifier ? 'id' : null)
            ->withAccessors(['id' => 'getId'])
            ->build();

        $sutArray = $sut->toArray();

        # Act
        $result = $this->performSystemAction($sut, $method, $args);

        # Smoke
        $this->assertNotEmpty($result->items);

        # Assert
        foreach ($result->items as $key => $item) {
            if (in_array($item, $sutArray)) {
                $this->assertSame($item, $sutArray[$key]);
                $this->assertSame($key, array_search($item, $sutArray));
            }
        }
    }

    public function mutatedMapDataProvider(): array
    {
        $this->initFaker();

        $itemCount = 10;
        $itemIds = $this->dummyList(fn () => $this->unique->slug, $itemCount);
        $map = $this->createDummyItemMapWithKeys($itemIds);
        $itemSubset = $this->dummyList(fn () => $map[array_rand($map)], $itemCount / 2);
        $combine = $this->createDummyItemMap(10);

        return [
            // diff
            $this->mut('diff', 'mapping=standard') => [
                'items' => $map,
                'identifier' => false,
                'method' => 'diff',
                'args' => [$combine],
            ],
            $this->mut('diff', 'mapping=auto') => [
                'items' => $map,
                'identifier' => true,
                'method' => 'diff',
                'args' => [$combine],
            ],

            // contrast
            $this->mut('contrast', 'mapping=standard') => [
                'items' => $map,
                'identifier' => false,
                'method' => 'contrast',
                'args' => [$combine],
            ],
            $this->mut('contrast', 'mapping=auto') => [
                'items' => $map,
                'identifier' => true,
                'method' => 'contrast',
                'args' => [$combine],
            ],

            // intersect
            $this->mut('intersect', 'mapping=standard') => [
                'items' => $map,
                'identifier' => false,
                'method' => 'intersect',
                'args' => [array_merge($combine, $itemSubset)],
            ],
            $this->mut('intersect', 'mapping=auto') => [
                'items' => $map,
                'identifier' => true,
                'method' => 'intersect',
                'args' => [array_merge($combine, $itemSubset)],
            ],

            // merge
            $this->mut('merge', 'mapping=standard') => [
                'items' => $map,
                'identifier' => false,
                'method' => 'merge',
                'args' => [$combine],
            ],
            $this->mut('merge', 'mapping=auto') => [
                'items' => $map,
                'identifier' => true,
                'method' => 'merge',
                'args' => [$combine],
            ],
        ];
    }
}
