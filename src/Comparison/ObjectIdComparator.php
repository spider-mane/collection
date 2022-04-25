<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractObjectComparator;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;

class ObjectIdComparator extends AbstractObjectComparator implements ObjectComparatorInterface
{
    protected function getComparisonFunction(): callable
    {
        return fn ($a, $b): int => spl_object_id($a) <=> spl_object_id($b);
    }
}
