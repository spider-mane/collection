<?php

namespace WebTheory\Collection\Contracts;

interface CollectionSorterInterface
{
    public function sort(array $array, string $order): array;

    public function ksort(array $array, string $order): array;
}
