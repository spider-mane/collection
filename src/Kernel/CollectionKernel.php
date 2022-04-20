<?php

namespace WebTheory\Collection\Kernel;

use ArrayIterator;
use IteratorAggregate;
use OutOfBoundsException;
use ReturnTypeWillChange;
use Traversable;
use WebTheory\Collection\Comparison\PropertyBasedCollectionComparator;
use WebTheory\Collection\Comparison\PropertyBasedObjectComparator;
use WebTheory\Collection\Comparison\RuntimeIdBasedCollectionComparator;
use WebTheory\Collection\Comparison\RuntimeIdBasedObjectComparator;
use WebTheory\Collection\Contracts\CollectionComparatorInterface;
use WebTheory\Collection\Contracts\CollectionKernelInterface;
use WebTheory\Collection\Contracts\CollectionQueryInterface;
use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Contracts\JsonSerializerInterface;
use WebTheory\Collection\Contracts\LoopInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;
use WebTheory\Collection\Contracts\OperationProviderInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
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
     * Callback function to create a new instance of the interfacing collection.
     *
     * @var callable
     */
    protected $generator;

    /**
     * Property to use as primary identifier for items in the collection.
     */
    protected ?string $identifier = null;

    /**
     * Whether or not to map the identifier to items in the collection.
     */
    protected bool $map = false;

    protected PropertyResolverInterface $propertyResolver;

    protected OperationProviderInterface $operationProvider;

    protected JsonSerializerInterface $jsonSerializer;

    public function __construct(
        array $items,
        callable $generator,
        ?string $identifier = null,
        array $accessors = [],
        ?bool $map = false,
        ?JsonSerializerInterface $jsonSerializer = null,
        ?OperationProviderInterface $operationProvider = null
    ) {
        $this->generator = $generator;
        $this->identifier = $identifier;

        $this->map = $map ?? $this->map ?? false;
        $this->jsonSerializer = $jsonSerializer ?? new BasicJsonSerializer();
        $this->operationProvider = $operationProvider ?? new Operations();

        $this->propertyResolver = new PropertyResolver($accessors);

        $this->collect(...$items);
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function collect(object ...$items): void
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(object $item): bool
    {
        if ($this->alreadyHasItem($item)) {
            return false;
        }

        $this->isMapped()
            ? $this->items[$this->getPropertyValue($item, $this->identifier)] = $item
            : $this->items[] = $item;

        return true;
    }

    public function remove($item): bool
    {
        if (is_object($item)) {
            $position = array_search($item, $this->items, true);

            unset($this->items[$position]);

            return true;
        }

        if ($this->isMapped() && isset($this->items[$item])) {
            unset($this->items[$item]);

            return true;
        }

        if ($this->contains($item)) {
            return $this->remove($this->findBy($this->identifier, $item));
        }

        return false;
    }

    public function contains($item): bool
    {
        if (is_object($item)) {
            return in_array($item, $this->items, true);
        }

        if ($this->isMapped()) {
            return isset($this->items[$item]);
        }

        if ($this->hasIdentifier()) {
            return !empty($this->findBy($this->identifier, $item));
        }

        return false;
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

    public function column(string $property): array
    {
        return array_map(
            fn ($item) => $this->getPropertyValue($item, $property),
            $this->items
        );
    }

    public function findBy(string $property, $value): object
    {
        $items = $this->getItemsWhere($property, '=', $value);

        if (!empty($items)) {
            return $items[0];
        }

        throw new OutOfBoundsException(
            "Cannot find item where {$property} is equal to {$value}."
        );
    }

    public function query(CollectionQueryInterface $query): object
    {
        return $this->spawnFrom(...$query->query($this->items));
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

    public function matches(array $collection): bool
    {
        return $this->getCollectionComparator()->matches($this->items, $collection);
    }

    public function diff(array $collection): object
    {
        return $this->spawnFrom(
            ...$this->getCollectionComparator()->diff($this->items, $collection)
        );
    }

    public function contrast(array $collection): object
    {
        return $this->spawnFrom(
            ...$this->getCollectionComparator()->contrast($this->items, $collection)
        );
    }

    public function intersect(array $collection): object
    {
        return $this->spawnFrom(
            ...$this->getCollectionComparator()->intersect($this->items, $collection)
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

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->items;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    protected function getCollectionComparator(): CollectionComparatorInterface
    {
        if ($this->hasIdentifier()) {
            $comparator = new PropertyBasedCollectionComparator($this->propertyResolver);
            $comparator->setProperty($this->identifier);
        } else {
            $comparator = new RuntimeIdBasedCollectionComparator();
        }

        return $comparator;
    }

    protected function getObjectComparator(): ObjectComparatorInterface
    {
        if ($this->hasIdentifier()) {
            $comparator = new PropertyBasedObjectComparator($this->propertyResolver);
            $comparator->setProperty($this->identifier);
        } else {
            $comparator = new RuntimeIdBasedObjectComparator();
        }

        return $comparator;
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

    protected function objectsMatch(object $a, object $b): bool
    {
        return $this->getObjectComparator()->matches($a, $b);
    }

    protected function alreadyHasItem(object $object): bool
    {
        $comparator = $this->getObjectComparator();

        foreach ($this->items as $item) {
            if ($comparator->matches($item, $object)) {
                return true;
            }
        }

        return false;
    }

    protected function getPropertyValue(object $item, string $property)
    {
        return $this->propertyResolver->resolveProperty($item, $property);
    }

    protected function getItemsWhere(string $property, string $operator, $value): array
    {
        return $this->getBasicQuery($property, $operator, $value)
            ->query($this->items);
    }

    protected function hasIdentifier(): bool
    {
        return !empty($this->identifier);
    }

    protected function isMapped(): bool
    {
        return $this->hasIdentifier() && true === $this->map;
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
