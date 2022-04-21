<?php

namespace WebTheory\Collection\Kernel;

use ArrayIterator;
use Closure;
use IteratorAggregate;
use OutOfBoundsException;
use Throwable;
use Traversable;
use WebTheory\Collection\Comparison\PropertyBasedCollectionComparator;
use WebTheory\Collection\Comparison\PropertyBasedObjectComparator;
use WebTheory\Collection\Comparison\RuntimeIdBasedCollectionComparator;
use WebTheory\Collection\Comparison\RuntimeIdBasedObjectComparator;
use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Contracts\CollectionComparatorInterface;
use WebTheory\Collection\Contracts\CollectionKernelInterface;
use WebTheory\Collection\Contracts\CollectionQueryInterface;
use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Contracts\JsonSerializerInterface;
use WebTheory\Collection\Contracts\LoopInterface;
use WebTheory\Collection\Contracts\OperationProviderInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Driver\AutoKeyedMap;
use WebTheory\Collection\Driver\IdentifiableItemList;
use WebTheory\Collection\Driver\StandardList;
use WebTheory\Collection\Enum\Order;
use WebTheory\Collection\Iteration\ForeachLoop;
use WebTheory\Collection\Json\BasicJsonSerializer;
use WebTheory\Collection\Query\BasicQuery;
use WebTheory\Collection\Query\Operation\Operations;
use WebTheory\Collection\Resolution\PropertyResolver;
use WebTheory\Collection\Sorting\MapBasedSorter;
use WebTheory\Collection\Sorting\PropertyBasedSorter;

class CollectionKernel implements CollectionKernelInterface, IteratorAggregate
{
    /**
     * Array of objects to be operated on.
     *
     * @var array<int,object>|array<string,object>
     */
    protected array $items = [];

    /**
     * Function to create a new instance of the interfacing collection.
     */
    protected Closure $generator;

    /**
     * Whether or not to map the identifier to items in the collection.
     */
    protected bool $map = false;

    protected ArrayDriverInterface $driver;

    protected PropertyResolverInterface $propertyResolver;

    protected CollectionComparatorInterface $aggregateComparator;

    protected OperationProviderInterface $operationsProvider;

    protected JsonSerializerInterface $jsonSerializer;

    public function __construct(
        array $items,
        Closure $generator,
        ?string $identifier = null,
        array $accessors = [],
        ?bool $map = false,
        ?JsonSerializerInterface $jsonSerializer = null,
        ?OperationProviderInterface $operations = null
    ) {
        $this->generator = $generator;

        $this->jsonSerializer = $jsonSerializer ?? new BasicJsonSerializer();
        $this->operationsProvider = $operations ?? new Operations();

        $this->propertyResolver = new PropertyResolver($accessors);

        $this->aggregateComparator = $identifier
            ? new PropertyBasedCollectionComparator($this->propertyResolver, $identifier)
            : new RuntimeIdBasedCollectionComparator();

        $objectComparator = $identifier
            ? new PropertyBasedObjectComparator($this->propertyResolver, $identifier)
            : new RuntimeIdBasedObjectComparator();

        if ($identifier && $map) {
            $this->driver = new AutoKeyedMap($identifier, $this->propertyResolver, $objectComparator);
        } elseif ($identifier) {
            $this->driver = new IdentifiableItemList($identifier, $this->propertyResolver, $objectComparator);
        } else {
            $this->driver = new StandardList($objectComparator);
        }

        $this->collect(...$items);
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function collect(object ...$items): void
    {
        array_map([$this, 'add'], $items);
    }

    public function add(object $item): bool
    {
        return $this->driver->insert($this->items, $item);
    }

    public function remove($item): bool
    {
        return $this->driver->remove($this->items, $item);
    }

    public function contains($item): bool
    {
        return $this->driver->contains($this->items, $item);
    }

    public function first(): object
    {
        return reset($this->items);
    }

    public function last(): object
    {
        $last = end($this->items);

        reset($this->items);

        return $last;
    }

    public function hasItems(): bool
    {
        return !empty($this->items);
    }

    public function hasWhere(string $property, string $operator, $value): bool
    {
        return !empty($this->getItemsWhere($property, $operator, $value));
    }

    public function firstWhere(string $property, string $operator, $value): object
    {
        $items = $this->getItemsWhere($property, $operator, $value);
        $exception = new OutOfBoundsException(
            "Cannot find item where {$property} is equal to {$value}."
        );

        return $this->extractFirstItem($items, $exception);
    }

    public function query(CollectionQueryInterface $query): object
    {
        return $this->spawnFrom(...$this->performQuery($query));
    }

    public function where(string $property, string $operator, $value): object
    {
        return $this->query($this->getBasicQuery($property, $operator, $value));
    }

    public function filter(callable $callback): object
    {
        return $this->spawnFrom(
            ...array_values(array_filter($this->items, $callback))
        );
    }

    public function column(string $property): array
    {
        return $this->map(
            fn ($item) => $this->getPropertyValue($item, $property)
        );
    }

    public function matches(array $collection): bool
    {
        return $this->aggregateComparator->matches($this->items, $collection);
    }

    public function diff(array $collection): object
    {
        return $this->spawnFrom(
            ...$this->aggregateComparator->diff($this->items, $collection)
        );
    }

    public function contrast(array $collection): object
    {
        return $this->spawnFrom(
            ...$this->aggregateComparator->contrast($this->items, $collection)
        );
    }

    public function intersect(array $collection): object
    {
        return $this->spawnFrom(
            ...$this->aggregateComparator->intersect($this->items, $collection)
        );
    }

    public function merge(array ...$collections): object
    {
        return $this->spawnFrom(
            ...array_merge(array_values($this->items), ...$collections)
        );
    }

    public function sortWith(CollectionSorterInterface $sorter, string $order = Order::Asc): object
    {
        return $this->spawnFrom(...$sorter->sort($this->items, $order));
    }

    public function sortBy(string $property, string $order = Order::Asc): object
    {
        return $this->sortWith(
            new PropertyBasedSorter($this->propertyResolver, $property),
            $order
        );
    }

    public function sortMapped(array $map, string $property, string $order = Order::Asc): object
    {
        return $this->sortWith(
            new MapBasedSorter($this->propertyResolver, $property, $map),
            $order
        );
    }

    public function sortCustom(callable $callback): object
    {
        $clone = clone $this;

        usort($clone->items, $callback);

        return $this->spawnWith($clone);
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }

    public function walk(callable $callback): void
    {
        array_walk($this->items, $callback);
    }

    public function loop(LoopInterface $loop, callable $callback): void
    {
        $loop->iterate($this->items, $callback);
    }

    public function foreach(callable $callback): void
    {
        $this->loop(new ForeachLoop(), $callback);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function toJson(): string
    {
        return $this->jsonSerializer->serialize($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function jsonSerialize(): array
    {
        return $this->items;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    protected function getBasicQuery(string $property, string $operator, $value): CollectionQueryInterface
    {
        return new BasicQuery(
            $this->propertyResolver,
            $property,
            $operator,
            $value,
            $this->operationsProvider
        );
    }

    protected function performQuery(CollectionQueryInterface $query): array
    {
        return $query->query($this->items);
    }

    protected function extractFirstItem(array $items, Throwable $throwable): object
    {
        try {
            return $items[0];
        } catch (Throwable $e) {
            throw $throwable;
        }
    }

    protected function getPropertyValue(object $item, string $property)
    {
        return $this->propertyResolver->resolveProperty($item, $property);
    }

    protected function getItemsWhere(string $property, string $operator, $value): array
    {
        return $this->performQuery($this->getBasicQuery($property, $operator, $value));
    }

    protected function spawnWith(self $clone): object
    {
        return ($this->generator)($clone);
    }

    protected function spawnFrom(object ...$items): object
    {
        $clone = clone $this;

        $clone->items = [];
        $clone->collect(...$items);

        return $this->spawnWith($clone);
    }
}
