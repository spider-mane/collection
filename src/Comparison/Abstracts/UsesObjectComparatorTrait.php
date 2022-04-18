<?php

namespace WebTheory\Collection\Comparison\Abstracts;

use WebTheory\Collection\Contracts\ObjectComparatorInterface;

trait UsesObjectComparatorTrait
{
    public function getComparisonFunction(): callable
    {
        return [$this->getObjectComparator(), 'comparison'];
    }

    abstract protected function getObjectComparator(): ObjectComparatorInterface;
}
