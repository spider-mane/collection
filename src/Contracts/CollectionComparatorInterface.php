<?php

namespace WebTheory\Collection\Contracts;

interface CollectionComparatorInterface
{
    public function matches(array $array1, array $array2): bool;
}
