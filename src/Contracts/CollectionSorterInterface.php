<?php

namespace WebTheory\Collection\Contracts;

interface CollectionSorterInterface
{
    public function sort(array $array, string $order): array;
}
