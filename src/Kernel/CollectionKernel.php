<?php

namespace WebTheory\Collection\Kernel;

use ArrayIterator;
use Closure;
use IteratorAggregate;
use Traversable;
use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Contracts\ArrayFusionInterface;
use WebTheory\Collection\Contracts\CollectionAggregateInterface;
use WebTheory\Collection\Contracts\CollectionComparatorInterface;
use WebTheory\Collection\Contracts\CollectionKernelInterface;
use WebTheory\Collection\Contracts\CollectionQueryInterface;
use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Contracts\JsonSerializerInterface;
use WebTheory\Collection\Contracts\LoopInterface;
use WebTheory\Collection\Contracts\OperationProviderInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Enum\Order;
use WebTheory\Collection\Fusion\Collection\FusionSelection;
use WebTheory\Collection\Fusion\Contrast;
use WebTheory\Collection\Fusion\Diff;
use WebTheory\Collection\Fusion\Intersection;
use WebTheory\Collection\Fusion\Merger;
use WebTheory\Collection\Iteration\ForeachLoop;
use WebTheory\Collection\Json\BasicJsonSerializer;
use WebTheory\Collection\Kernel\Factory\CollectionKernelSubsystemFactory;
use WebTheory\Collection\Query\BasicQuery;
use WebTheory\Collection\Query\Operation\Operations;
use WebTheory\Collection\Sorting\MappedSorter;
use WebTheory\Collection\Sorting\PropertySorter;

class CollectionKernel implements CollectionKernelInterface, IteratorAggregate
{
    /**
     * Array of objects to be operated on.
     *
     * @var array<int|string,object>
     */
    protected array $items = [];

    /**
     * Function to create a new instance of the client collection class when
     * performing an operation that spawns a new collection. The callback will
     * be passed a clone of the kernel with the mutated array.
     */
    protected Closure $generator;

    protected ArrayDriverInterface $driver;

    protected PropertyResolverInterface $propertyResolver;

    protected CollectionComparatorInterface $collectionComparator;

    protected OperationProviderInterface $operationProvider;

    protected JsonSerializerInterface $jsonSerializer;

    protected FusionSelection $fusions;

    public function __construct(
        array $items,
        Closure $generator,
        ?string $identifier = null,
        array $accessors = [],
        bool $mapToIdentifier = false,
        ?JsonSerializerInterface $jsonSerializer = null,
        ?OperationProviderInterface $operationProvider = null
    ) {
        $this->generator = $generator;

        $this->jsonSerializer = $jsonSerializer ?? new BasicJsonSerializer();
        $this->operationProvider = $operationProvider ?? new Operations();

        $subsystems = new CollectionKernelSubsystemFactory(
            $identifier,
            $accessors,
            $mapToIdentifier
        );

        $this->driver = $subsystems->getArrayDriver();
        $this->propertyResolver = $subsystems->getPropertyResolver();
        $this->collectionComparator = $subsystems->getCollectionComparator();

        $objectComparator = $subsystems->getObjectComparator();

        $this->fusions = new FusionSelection([
            'contrast' => new Contrast($objectComparator),
            'diff' => new Diff($objectComparator),
            'intersect' => new Intersection($objectComparator),
            'merge' => new Merger(),
        ]);

        $this->collect($items);
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function collect(array $items): void
    {
        array_walk($items, [$this, 'insert']);
    }

    public function insert(object $item, $offset = null): bool
    {
        return $this->driver->insert($this->items, $item, $offset);
    }

    public function fetch($item): object
    {
        return $this->driver->fetch($this->items, $item);
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

    public function firstWhere(string $property, string $operator, $value): ?object
    {
        $items = $this->getItemsWhere($property, $operator, $value);

        return reset($items) ?: null;
    }

    public function query(CollectionQueryInterface $query): object
    {
        return $this->spawnFrom($this->performQuery($query));
    }

    public function where(string $property, string $operator, $value): object
    {
        return $this->query($this->getBasicQuery($property, $operator, $value));
    }

    public function filter(callable $callback): object
    {
        return $this->spawnFrom(array_filter($this->items, $callback));
    }

    public function matches(array $collection): bool
    {
        return $this->collectionComparator->matches($this->items, $collection);
    }

    public function remix(ArrayFusionInterface $fusion, array ...$collections): object
    {
        return $this->spawnFrom($fusion->remix($this->items, ...$collections));
    }

    public function diff(array ...$collections): object
    {
        return $this->getRemix('diff', ...$collections);
    }

    public function contrast(array ...$collections): object
    {
        return $this->getRemix('contrast', ...$collections);
    }

    public function intersect(array ...$collections): object
    {
        return $this->getRemix('intersect', ...$collections);
    }

    public function merge(array ...$collections): object
    {
        return $this->getRemix('merge', ...$collections);
    }

    public function sort(CollectionSorterInterface $sorter, string $order = Order::Asc): object
    {
        return $this->spawnFrom($sorter->sort($this->items, $order));
    }

    public function sortBy(string $property, string $order = Order::Asc): object
    {
        return $this->sort(
            new PropertySorter($this->propertyResolver, $property),
            $order
        );
    }

    public function sortMapped(array $map, string $property, string $order = Order::Asc): object
    {
        return $this->sort(
            new MappedSorter($this->propertyResolver, $property, $map),
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

    public function operate(CollectionAggregateInterface $aggregate, array ...$collections)
    {
        return $aggregate->operate($this->items, ...$collections);
    }

    public function column(string $property): array
    {
        return $this->map(
            fn ($item) => $this->getPropertyValue($item, $property)
        );
    }

    public function values(): array
    {
        return array_values($this->items);
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
            $this->operationProvider
        );
    }

    protected function getRemix(string $fusion, array ...$collections): object
    {
        return $this->remix($this->fusions->fetch($fusion), ...$collections);
    }

    protected function getPropertyValue(object $item, string $property)
    {
        return $this->propertyResolver->resolveProperty($item, $property);
    }

    protected function performQuery(CollectionQueryInterface $query): array
    {
        return $query->query($this->items);
    }

    protected function getItemsWhere(string $property, string $operator, $value): array
    {
        return $this->performQuery(
            $this->getBasicQuery($property, $operator, $value)
        );
    }

    protected function spawnWith(CollectionKernel $clone): object
    {
        return ($this->generator)($clone);
    }

    protected function spawnFrom(array $items): object
    {
        $clone = clone $this;

        $clone->items = [];
        $clone->collect($items);

        return $this->spawnWith($clone);
    }
}
