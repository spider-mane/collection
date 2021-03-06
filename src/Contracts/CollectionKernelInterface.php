<?php

namespace WebTheory\Collection\Contracts;

use Countable;
use JsonSerializable;
use Traversable;
use WebTheory\Collection\Enum\Order;

interface CollectionKernelInterface extends Traversable, Countable, JsonSerializable
{
    public function collect(array $items): void;

    public function fetch($item): object;

    public function insert(object $item, $offset = null): bool;

    public function remove($item): bool;

    public function contains($item): bool;

    public function hasItems(): bool;

    public function first(): object;

    public function last(): object;

    public function query(CollectionQueryInterface $query): object;

    public function where(string $property, string $operator, $value): object;

    public function hasWhere(string $property, string $operator, $value): bool;

    public function firstWhere(string $property, string $operator, $value): ?object;

    public function filter(callable $callback): object;

    public function matches(array $collection): bool;

    public function remix(ArrayFusionInterface $fusion, array ...$collections): object;

    public function diff(array ...$collections): object;

    public function contrast(array ...$collections): object;

    public function intersect(array ...$collections): object;

    public function merge(array ...$collections): object;

    public function sort(CollectionSorterInterface $sorter, string $order = Order::Asc): object;

    public function sortBy(string $property, string $order = Order::Asc): object;

    public function sortMapped(array $map, string $property, string $order = Order::Asc): object;

    public function sortCustom(callable $callback, string $order = Order::Asc): object;

    public function map(callable $callback): array;

    public function walk(callable $callback): void;

    public function loop(LoopInterface $loop, callable $callback): void;

    public function foreach(callable $callback): void;

    public function operate(CollectionAggregateInterface $aggregate, array ...$collections);

    public function column(string $property): array;

    public function values(): array;

    public function toArray(): array;

    public function toJson(): string;
}
