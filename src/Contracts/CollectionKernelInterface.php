<?php

namespace WebTheory\Collection\Contracts;

use Countable;
use JsonSerializable;
use Traversable;
use WebTheory\Collection\Enum\Order;

interface CollectionKernelInterface extends Traversable, Countable, JsonSerializable
{
    public function collect(object ...$items): void;

    public function add(object $item): bool;

    public function remove($item): bool;

    public function contains($item): bool;

    public function first(): object;

    public function last(): object;

    public function hasItems(): bool;

    public function column(string $property): array;

    public function findBy(string $property, $value): object;

    public function query(CollectionQueryInterface $query): object;

    public function where(string $property, string $operator, $value): object;

    public function filter(callable $callback): object;

    public function matches(array $collection): bool;

    public function diff(array $collection): object;

    public function contrast(array $collection): object;

    public function intersect(array $collection): object;

    public function merge(array ...$collections): object;

    public function sortWith(CollectionSorterInterface $sorter, string $order = Order::Asc): object;

    public function sortBy(string $property, string $order = Order::Asc): object;

    public function sortMapped(array $map, string $property, string $order = Order::Asc): object;

    public function sortCustom(callable $callback): object;

    public function map(callable $callback): array;

    public function walk(callable $callback): void;

    public function loop(LoopInterface $loop, callable $callback): void;

    public function foreach(callable $callback): void;

    public function toArray(): array;

    public function toJson(): string;
}
