<?php

namespace WebTheory\Collection\Comparison\Abstracts;

use WebTheory\Collection\Contracts\ObjectComparatorInterface;

abstract class AbstractObjectComparator implements ObjectComparatorInterface
{
    public function comparison(object $object1, object $object2): int
    {
        return ($this->getComparisonFunction())($object1, $object2);
    }

    public function matches(object $object1, object $object2): bool
    {
        return $this->comparison($object1, $object2) === 0;
    }

    abstract protected function getComparisonFunction(): callable;
}
