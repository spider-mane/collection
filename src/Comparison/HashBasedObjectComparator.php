<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractObjectComparator;

class HashBasedObjectComparator extends AbstractObjectComparator
{
    public function getComparisonFunction(): callable
    {
        return fn ($a, $b): int => spl_object_hash($a) <=> spl_object_hash($b);
    }
}
