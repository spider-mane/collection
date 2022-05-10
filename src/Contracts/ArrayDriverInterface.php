<?php

namespace WebTheory\Collection\Contracts;

interface ArrayDriverInterface
{
    public function fetch(array $array, $item);

    public function collect(array &$array, array $items): void;

    public function insert(array &$array, object $item, $offset = null): bool;

    public function remove(array &$array, $item): bool;

    public function contains(array $array, $item): bool;
}
