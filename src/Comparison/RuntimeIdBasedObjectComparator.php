<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractObjectComparator;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;

class RuntimeIdBasedObjectComparator extends AbstractObjectComparator implements ObjectComparatorInterface
{
    public function getComparisonFunction(): callable
    {
        return fn ($a, $b): int => spl_object_id($a) <=> spl_object_id($b);
    }
}
