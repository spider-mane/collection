<?php

namespace WebTheory\Collection\Kernel;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use OutOfBoundsException;
use Traversable;
use WebTheory\Collection\Contracts\CollectionKernelInterface;
use WebTheory\Collection\Contracts\OrderInterface;
use WebTheory\Collection\Sorting\Order;

class CollectionKernel implements CollectionKernelInterface, IteratorAggregate
{
    protected array $items = [];

    protected ?string $identifier = null;

    protected bool $map = false;

    protected array $accessors = [];

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
            $this->remove($this->findById($item));
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
        if ($this->isEmpty()) {
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
        if ($this->isEmpty()) {
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
        $this->validateOrder($order);

        $collection = clone $this;

        usort(
            $collection->items,
            $this->getSortingFunction($property, $order)
        );

        return $this->collect(...$collection->items);
    }

    public function sortMapped(array $map, $order = OrderInterface::ASC, string $property = null): object
    {
        $this->validateOrder($order);

        $collection = clone $this;

        usort(
            $collection->items,
            $this->getSortingMappedFunction($map, $order, $property)
        );

        return $this->collect(...$collection->items);
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

        return $items[0];
    }

    public function findById($id)
    {
        return $this->find($this->identifier, $id);
    }

    public function filter(callable $callback): object
    {
        return $this->collect(...$this->getFilteredItems($callback));
    }

    public function whereEquals(string $property, $value): object
    {
        return $this->filter(
            fn ($item) => $this->resolveValue($item, $property) === $value
        );
    }

    public function whereNotEquals(string $property, $value): object
    {
        return $this->filter(
            fn ($item) => $this->resolveValue($item, $property) !== $value
        );
    }

    public function whereGreaterThan(string $property, $value): object
    {
        return $this->filter(
            fn ($item) => $this->resolveValue($item, $property) > $value
        );
    }

    public function whereLessThan(string $property, $value): object
    {
        return $this->filter(
            fn ($item) => $this->resolveValue($item, $property) < $value
        );
    }

    public function whereGreaterThanOrEquals(string $property, $value): object
    {
        return $this->filter(
            fn ($item) => $this->resolveValue($item, $property) >= $value
        );
    }

    public function whereLessThanOrEquals(string $property, $value): object
    {
        return $this->filter(
            fn ($item) => $this->resolveValue($item, $property) <= $value
        );
    }

    public function whereIn(string $property, array $values): object
    {
        return $this->filter(
            fn ($item) => in_array($this->resolveValue($item, $property), $values)
        );
    }

    public function whereNotIn(string $property, array $values): object
    {
        return $this->filter(
            fn ($item) => !in_array($this->resolveValue($item, $property), $values)
        );
    }

    public function without($property, ...$values): object
    {
        return $this->filter(
            fn ($item) => !in_array(
                $this->resolveValue($item, $property),
                $values
            )
        );
    }

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
                case true:
                    break 2;

                case false:
                    continue 2;
            }
        }
    }

    public function diff(array $other): object
    {
        $diffAtoB = array_udiff(
            $this->items,
            $other,
            $this->getComparisonFunction()
        );
        $diffBtoA = array_udiff(
            $other,
            $this->items,
            $this->getComparisonFunction()
        );

        $diff = array_merge($diffAtoB, $diffBtoA);

        $collection = clone $this;
        $collection->items = $diff;

        return $this->collect(...$collection->items);
    }

    public function differsFrom(array $other): bool
    {
        return !$this->diff($other)->isEmpty();
    }

    public function intersect(array $other): object
    {
        $intersect = array_uintersect(
            $this->items,
            $other,
            $this->getComparisonFunction()
        );

        $collection = clone $this;
        $collection->items = $intersect;

        return $this->collect(...$collection->items);
    }

    public function merge(array ...$collections): object
    {
        $merged = clone $this;

        foreach ($collections as $collection) {
            foreach ($collection as $value) {
                $this->add($value);
            }
        }

        return $this->collect(...$merged->items);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function toJson(): string
    {
        return json_encode($this->items);
    }

    public function jsonSerialize(): mixed
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
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

    protected function getSortingFunction(string $property, string $order = OrderInterface::ASC): callable
    {
        return function ($a, $b) use ($property, $order): int {
            $a = $this->resolveValue($a, $property);
            $b = $this->resolveValue($b, $property);

            return $this->resolveEntriesOrder($a, $b, $order);
        };
    }

    protected function getSortingMappedFunction(
        array $map,
        string $order = OrderInterface::ASC,
        ?string $property = null
    ): callable {
        $property = $property ?? $this->identifier;

        return function ($a, $b) use ($map, $property, $order): int {
            // Set value to 0 if one is not provided
            $a = (int) $map[$this->resolveValue($a, $property)] ?? 0;
            $b = (int) $map[$this->resolveValue($b, $property)] ?? 0;

            return $this->resolveEntriesOrder($a, $b, $order);
        };
    }

    protected function resolveEntriesOrder($a, $b, string $order): int
    {
        return ($a <=> $b) * ($order === OrderInterface::DESC ? -1 : 1);
    }

    protected function getComparisonFunction(): callable
    {
        return function ($a, $b): int {
            if ($this->hasIdentifier()) {
                $a = $this->resolveValue($a, $this->identifier);
                $b = $this->resolveValue($b, $this->identifier);
            } else {
                $a = spl_object_hash($a);
                $b = spl_object_hash($b);
            }

            return $a <=> $b;
        };
    }

    protected function alreadyHasItem(object $object): bool
    {
        foreach ($this->items as $item) {
            if ($this->objectsAreIdentical($item, $object)) {
                return true;
            }
        }

        return false;
    }

    protected function objectsAreIdentical(object $a, object $b): bool
    {
        return $this->getComparisonFunction()($a, $b) === 0;
    }

    protected function resolveValue(object $item, string $property)
    {
        $accessor = $this->resolvePropertyAccessor($property);

        if ($accessor && method_exists($item, $accessor)) {
            return $item->{$accessor}();
        }

        if (property_exists($item, $property)) {
            return $item->$property;
        }

        throw new LogicException(
            sprintf(
                'No method of access for "%s" in %s has been defined.',
                $property,
                get_class($item)
            )
        );
    }

    public function collect(object ...$items): object
    {
        return ($this->factory)(...$items);
    }

    protected function resolvePropertyAccessor(string $property): ?string
    {
        return $this->accessors[$property] ?? null;
    }

    protected function hasIdentifier(): bool
    {
        return !empty($this->identifier);
    }

    protected function isMapped(): bool
    {
        return $this->hasIdentifier() && true === $this->map;
    }

    protected function validateOrder(string $order): void
    {
        Order::throwExceptionIfInvalid($order);
    }
}
