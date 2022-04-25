<?php

namespace WebTheory\Collection\Comparison\Abstracts;

use WebTheory\Collection\Contracts\CollectionComparatorInterface;

abstract class AbstractCollectionComparator implements CollectionComparatorInterface
{
    public function matches(array $array1, array $array2): bool
    {
        return empty(array_udiff(
            $array1,
            $array2,
            $this->getComparisonFunction()
        ));
    }

    abstract protected function getComparisonFunction(): callable;
}
