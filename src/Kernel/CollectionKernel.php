<?php

namespace WebTheory\Collection\Kernel;

use ArrayIterator;
use Enum\LoopAction;
use IteratorAggregate;
use LogicException;
use OutOfBoundsException;
use Traversable;
use WebTheory\Collection\Comparison\HashBasedCollectionComparator;
use WebTheory\Collection\Comparison\HashBasedObjectComparator;
use WebTheory\Collection\Comparison\PropertyBasedCollectionComparator;
use WebTheory\Collection\Comparison\PropertyBasedObjectComparator;
use WebTheory\Collection\Comparison\RuntimeIdBasedCollectionComparator;
use WebTheory\Collection\Comparison\RuntimeIdBasedObjectComparator;
use WebTheory\Collection\Contracts\CollectionComparatorInterface;
use WebTheory\Collection\Contracts\CollectionKernelInterface;
use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;
use WebTheory\Collection\Contracts\OrderInterface;
use WebTheory\Collection\Resolution\PropertyResolver;
use WebTheory\Collection\Sorting\MapBasedSorter;
use WebTheory\Collection\Sorting\PropertyBasedSorter;

class CollectionKernel implements CollectionKernelInterface, IteratorAggregate
{
    protected array $items = [];

    protected ?string $identifier = null;

    protected bool $map = false;

    protected int $jsonFlags;

    protected PropertyResolver $propertyResolver;

    protected PropertyBasedSorter $propertyBasedSorter;

    protected MapBasedSorter $mapBasedSorter;

    protected PropertyBasedCollectionComparator $propertyBasedCollectionComparator;

    protected HashBasedCollectionComparator $hashBasedCollectionComparator;

    protected RuntimeIdBasedCollectionComparator $idBasedCollectionComparator;

    protected PropertyBasedObjectComparator $propertyBasedObjectComparator;

    protected HashBasedObjectComparator $hashBasedObjectComparator;

    protected RuntimeIdBasedObjectComparator $idBasedObjectComparator;

    /**
     * @var callable
     */
    protected $factory;

    public function __construct(
        array $items,
        callable $factory,
        ?string $identifier = null,
        array $accessors = [],
        ?bool $map = false
    ) {
        $this->factory = $factory;
        $this->identifier = $identifier;
        $this->accessors = $accessors;
        $this->map = $map ?? $this->map ?? false;

        $this->propertyResolver = new PropertyResolver($this->accessors);

        $this->propertyBasedSorter = new PropertyBasedSorter($this->propertyResolver);
        $this->mapBasedSorter = new MapBasedSorter($this->propertyResolver);

        $this->propertyBasedCollectionComparator = new PropertyBasedCollectionComparator($this->propertyResolver);
        $this->hashBasedCollectionComparator = new HashBasedCollectionComparator($this->propertyResolver);
        $this->idBasedCollectionComparator = new RuntimeIdBasedCollectionComparator($this->propertyResolver);

        $this->propertyBasedObjectComparator = new PropertyBasedObjectComparator($this->propertyResolver);
        $this->hashBasedObjectComparator = new HashBasedObjectComparator($this->propertyResolver);
        $this->idBasedObjectComparator = new RuntimeIdBasedObjectComparator($this->propertyResolver);

        array_map([$this, 'add'], $items);
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function add(object $item): bool
    {
        if ($this->alreadyHasItem($item)) {
            return false;
        }

        $this->isMapped()
            ? $this->items[$this->resolveValue($item, $this->identifier)] = $item
            : $this->items[] = $item;

        return true;
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
            return !empty($this->find($this->identifier, $item));
        }

        return false;
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
            return $this->remove($this->findById($item));
        }

        return false;
    }

    public function column(string $property): array
    {
        $temp = [];

        foreach ($this->items as $item) {
            $value = $this->resolveValue($item, $property);

            $temp[] = $value;
        }

        return $temp;
    }

    public function first(): object
    {
        if (!$this->hasItems()) {
            throw new OutOfBoundsException(
                "Can't determine first item. Collection is empty"
            );
        }

        reset($this->items);

        $first = current($this->items);

        return $first;
    }

    public function last(): object
    {
        if (!$this->hasItems()) {
            throw new OutOfBoundsException(
                'Can\'t determine last item. Collection is empty'
            );
        }

        $last = end($this->items);

        reset($this->items);

        return $last;
    }

    public function sortBy(string $property, string $order = OrderInterface::ASC): object
    {
        $sorter = $this->propertyBasedSorter
            ->setProperty($property);

        return $this->sortWith($sorter, $order);
    }

    public function sortMapped(array $map, $order = OrderInterface::ASC, string $property = null): object
    {
        if (!$property ??= $this->identifier) {
            throw new LogicException(
                'Cannot sort by map without property or item identifier set.'
            );
        }

        $sorter = $this->mapBasedSorter
            ->setMap($map)
            ->setProperty($property);

        return $this->sortWith($sorter, $order);
    }

    public function sortWith(CollectionSorterInterface $sorter, $order = OrderInterface::ASC): object
    {
        $collection = clone $this;

        $items = $sorter->sort($collection->items, $order);

        return $this->collect(...$items);
    }

    public function sortCustom(callable $callback): object
    {
        $collection = clone $this;

        usort($collection->items, $callback);

        return $this->collect(...$collection->items);
    }

    public function find(string $property, $value): object
    {
        $items = $this->getFilteredItems(
            fn ($item) => $this->resolveValue($item, $property) === $value
        );

        if ($items) {
            return $items[0];
        }

        throw new OutOfBoundsException(
            "Can't find item with {$property} is equal to {$value}."
        );
    }

    public function findById($id)
    {
        if (!$this->hasIdentifier()) {
            throw new LogicException(
                "Use of " . __METHOD__ . " requires an identifier."
            );
        }

        return $this->find($this->identifier, $id);
    }

    public function filter(callable $callback): object
    {
        return $this->collect(...$this->getFilteredItems($callback));
    }

    public function strip($property, $values): object
    {
        return $this->filter(
            fn ($item) => !in_array(
                $this->resolveValue($item, $property),
                $values
            )
        );
    }

    public function whereEquals(string $property, $value): object
    {
        return $this->filter(
            fn ($item) => $this->resolveValue($item, $property) === $value
        );
    }

    // public function whereNotEquals(string $property, $value): object
    // {
    //     return $this->filter(
    //         fn ($item) => $this->resolveValue($item, $property) !== $value
    //     );
    // }

    // public function whereGreaterThan(string $property, $value): object
    // {
    //     return $this->filter(
    //         fn ($item) => $this->resolveValue($item, $property) > $value
    //     );
    // }

    // public function whereLessThan(string $property, $value): object
    // {
    //     return $this->filter(
    //         fn ($item) => $this->resolveValue($item, $property) < $value
    //     );
    // }

    // public function whereGreaterThanOrEquals(string $property, $value): object
    // {
    //     return $this->filter(
    //         fn ($item) => $this->resolveValue($item, $property) >= $value
    //     );
    // }

    // public function whereLessThanOrEquals(string $property, $value): object
    // {
    //     return $this->filter(
    //         fn ($item) => $this->resolveValue($item, $property) <= $value
    //     );
    // }

    // public function whereIn(string $property, array $values): object
    // {
    //     return $this->filter(
    //         fn ($item) => in_array($this->resolveValue($item, $property), $values)
    //     );
    // }

    // public function whereNotIn(string $property, array $values): object
    // {
    //     return $this->filter(
    //         fn ($item) => !in_array($this->resolveValue($item, $property), $values)
    //     );
    // }

    public function spawn(callable $callback): object
    {
        return $this->collect(...$this->map($callback));
    }

    public function map(callable $callback)
    {
        return array_map($callback, $this->items);
    }

    public function walk(callable $callback): void
    {
        array_walk($this->items, $callback);
    }

    public function foreach(callable $callback): void
    {
        foreach ($this->items as $key => $item) {
            $action = $callback($item, $key, $this->items);

            switch ($action) {
                case LoopAction::Break():
                case true:
                    break 2;

                case LoopAction::Continue():
                case false:
                    continue 2;
            }
        }
    }

    public function notIn(array $items): object
    {
        $collection = clone $this;

        $items = $this->getResolvedCollectionComparator()
            ->notIn($collection->items, $items);

        return $this->collect(...$items);
    }

    public function difference(array $items): object
    {
        $collection = clone $this;

        $items = $this->getResolvedCollectionComparator()
            ->difference($collection->items, $items);

        return $this->collect(...$items);
    }

    public function intersection(array $items): object
    {
        $collection = clone $this;

        $items = $this->getResolvedCollectionComparator()
            ->intersection($collection->items, $items);

        return $this->collect(...$items);
    }

    public function matches(array $items): bool
    {
        return $this->getResolvedCollectionComparator()
            ->matches($this->items, $items);
    }

    public function merge(array ...$collections): object
    {
        $clone = clone $this;

        foreach ($collections as $collection) {
            foreach ($collection as $value) {
                $clone->add($value);
            }
        }

        return $this->collect(...$clone->items);
    }

    public function collect(object ...$items): object
    {
        return ($this->factory)(...$items);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function toJson(): string
    {
        return json_encode($this->items, JSON_THROW_ON_ERROR | $this->jsonFlags);
    }

    public function jsonSerialize(): mixed
    {
        return $this->items;
    }

    public function hasItems(): bool
    {
        return !empty($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    protected function getFilteredItems(callable $callback): array
    {
        $collection = clone $this;

        return array_values(array_filter($collection->items, $callback));
        // return array_merge([], array_filter($collection->items, $callback));
    }

    protected function getResolvedCollectionComparator(): CollectionComparatorInterface
    {
        return ($this->hasIdentifier())
            ? $this->propertyBasedCollectionComparator->setProperty($this->identifier)
            : $this->idBasedCollectionComparator;
    }

    protected function getResolvedObjectComparator(): ObjectComparatorInterface
    {
        return $this->hasIdentifier()
            ? $this->propertyBasedObjectComparator->setProperty($this->identifier)
            : $this->idBasedObjectComparator;
    }

    protected function objectsMatch(object $a, object $b): bool
    {
        return $this->getResolvedObjectComparator()->matches($a, $b);
    }

    protected function alreadyHasItem(object $object): bool
    {
        $comparator = $this->getResolvedObjectComparator();

        foreach ($this->items as $item) {
            if ($comparator->matches($item, $object)) {
                return true;
            }
        }

        return false;
    }

    protected function resolveValue(object $item, string $property)
    {
        return $this->propertyResolver->resolveProperty($item, $property);
    }

    protected function hasIdentifier(): bool
    {
        return !empty($this->identifier);
    }

    protected function isMapped(): bool
    {
        return $this->hasIdentifier() && true === $this->map;
    }
}
