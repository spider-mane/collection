<?php

namespace WebTheory\Collection\Sorting;

use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;
use WebTheory\Collection\Sorting\Abstracts\AbstractSorter;

class SimpleSorter extends AbstractSorter implements CollectionSorterInterface
{
    protected ObjectComparatorInterface $comparator;

    public function __construct(ObjectComparatorInterface $comparator)
    {
        $this->comparator = $comparator;
    }

    protected function compare(object $a, object $b): int
    {
        return $this->comparator->comparison($a, $b);
    }
}
