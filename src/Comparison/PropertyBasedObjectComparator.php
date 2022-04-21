<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractPropertyBasedObjectComparator;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;

class PropertyBasedObjectComparator extends AbstractPropertyBasedObjectComparator implements ObjectComparatorInterface
{
    public function getComparisonFunction(): callable
    {
        return fn (
            $a,
            $b
        ): int => $this->resolveValue($a) <=> $this->resolveValue($b);
    }
}
