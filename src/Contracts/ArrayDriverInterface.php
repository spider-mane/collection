<?php

namespace WebTheory\Collection\Contracts;

interface ArrayDriverInterface
{
    public function retrieve(array $array, $item);

    public function insert(array &$array, object $item, $locator = null): bool;

    public function remove(array &$array, $item): bool;

    public function contains(array $array, $item): bool;
}
