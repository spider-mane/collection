<?php

namespace WebTheory\Collection\Contracts;

interface CollectionComparatorInterface
{
    public function notIn(array $array, array $values): array;

    public function difference(array $array1, array $array2): array;

    public function intersection(array $array1, array $array2): array;

    public function matches(array $array1, array $array2): bool;

    public function getComparisonFunction(): callable;
}
