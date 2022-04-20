<?php

namespace WebTheory\Collection\Contracts;

interface CollectionComparatorInterface
{
    public function diff(array $array, array $values): array;

    public function contrast(array $array1, array $array2): array;

    public function intersect(array $array1, array $array2): array;

    public function matches(array $array1, array $array2): bool;

    public function getComparisonFunction(): callable;
}
