<?php

namespace WebTheory\Collection\Kernel;

use ArrayIterator;
use IteratorAggregate;
use LogicException;
use OutOfBoundsException;
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
use WebTheory\Collection\Contracts\ObjectComparatorInterface;
use WebTheory\Collection\Contracts\OrderInterface;
use WebTheory\Collection\Enum\LoopAction;
use WebTheory\Collection\Json\BasicJsonSerializer;
use WebTheory\Collection\Query\BasicQuery;
use WebTheory\Collection\Resolution\PropertyResolver;
use WebTheory\Collection\Sorting\MapBasedSorter;
use WebTheory\Collection\Sorting\PropertyBasedSorter;

class CollectionKernel implements CollectionKernelInterface, IteratorAggregate
{
    protected array $items = [];

    /**
     * @var callable
     */
    protected $generator;

    protected ?string $identifier = null;

    protected bool $map = false;

    protected JsonSerializerInterface $jsonSerializer;

    protected PropertyResolver $propertyResolver;

    public function __construct(
        array $items,
        callable $generator,
        ?string $identifier = null,
        array $accessors = [],
        ?bool $map = false,
        ?JsonSerializerInterface $jsonSerializer = null
    ) {
        $this->generator = $generator;
        $this->identifier = $identifier;

        $this->map = $map ?? $this->map ?? false;
        $this->jsonSerializer = $jsonSerializer ?? new BasicJsonSerializer();

        $this->propertyResolver = new PropertyResolver($accessors);

        array_map([$this, 'add'], $items);
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
            ? $this->items[$this->resolveValue($item, $this->identifier)] = $item
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
            return $this->remove($this->findById($item));
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
            return !empty($this->find($this->identifier, $item));
        }

        return false;
    }

    public function sortWith(CollectionSorterInterface $sorter, $order = OrderInterface::ASC): object
    {
        return $this->spawnFrom(...$sorter->sort($this->items, $order));
    }

    public function sortBy(string $property, string $order = OrderInterface::ASC): object
    {
        return $this->sortWith(
            new PropertyBasedSorter($this->propertyResolver, $property),
            $order
        );
    }

    public function sortMapped(array $map, $order = OrderInterface::ASC, string $property = null): object
    {
        if (!$property ??= $this->identifier) {
            throw new LogicException(
                'Cannot sort by map without property or item identifier set.'
            );
        }

        return $this->sortWith(
            new MapBasedSorter($this->propertyResolver, $property, $map),
            $order
        );
    }

    public function sortCustom(callable $callback): object
    {
        $clone = clone $this;

        usort($clone->items, $callback);

        return $this->spawn($clone);
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

    public function find(string $property, $value): object
    {
        $items = $this->getItemsWhere($property, '=', $value);

        if (!empty($items)) {
            return $items[0];
        }

        throw new OutOfBoundsException(
            "Can't find item where {$property} is equal to {$value}."
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
        return $this->spawnFrom(
            ...array_values(array_filter($this->items, $callback))
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

    public function whereEquals(string $property, $value): object
    {
        return $this->where($property, '=', $value);
    }

    public function whereNotIn($property, $values): object
    {
        return $this->where($property, 'not in', $values);
    }

    public function column(string $property): array
    {
        return array_map(
            fn ($item) => $this->resolveValue($item, $property),
            $this->items
        );
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

            if ($action instanceof LoopAction) {
                switch ($action->getValue()) {
                    case LoopAction::Break:
                        break 2;

                    case LoopAction::Continue:
                        continue 2;
                }
            }
        }
    }

    public function notIn(array $items): object
    {
        $clone = clone $this;

        $clone->items = $this->getResolvedCollectionComparator()
            ->notIn($clone->items, $items);

        return $this->spawn($clone);
    }

    public function difference(array $items): object
    {
        $clone = clone $this;

        $clone->items = $this->getResolvedCollectionComparator()
            ->difference($clone->items, $items);

        return $this->spawn($clone);
    }

    public function intersection(array $items): object
    {
        $clone = clone $this;

        $clone->items = $this->getResolvedCollectionComparator()
            ->intersection($clone->items, $items);

        return $this->spawn($clone);
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
            $clone->collect(...$collection);
        }

        return $this->spawn($clone);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function toJson(): string
    {
        return $this->jsonSerializer->serialize($this->items);
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

    protected function getResolvedCollectionComparator(): CollectionComparatorInterface
    {
        if ($this->hasIdentifier()) {
            $comparator = new PropertyBasedCollectionComparator($this->propertyResolver);
            $comparator->setProperty($this->identifier);
        } else {
            $comparator = new RuntimeIdBasedCollectionComparator();
        }

        return $comparator;
    }

    protected function getResolvedObjectComparator(): ObjectComparatorInterface
    {
        if ($this->hasIdentifier()) {
            $comparator = new PropertyBasedObjectComparator($this->propertyResolver);
            $comparator->setProperty($this->identifier);
        } else {
            $comparator = new RuntimeIdBasedObjectComparator();
        }

        return $comparator;
    }

    protected function getBasicQuery(string $property, string $operator, $value): BasicQuery
    {
        return new BasicQuery($this->propertyResolver, $property, $operator, $value);
    }

    protected function getItemsWhere(string $property, string $operator, $value): array
    {
        return $this->getBasicQuery($property, $operator, $value)
            ->query($this->items);
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

    protected function spawn(self $clone): object
    {
        return ($this->generator)($clone);
    }

    protected function spawnFrom(object ...$items): object
    {
        $clone = clone $this;

        $clone->items = $items;

        return $this->spawn($clone);
    }
}
