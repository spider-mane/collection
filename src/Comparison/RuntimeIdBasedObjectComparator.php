<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractObjectComparator;

class RuntimeIdBasedObjectComparator extends AbstractObjectComparator
{
    public function getComparisonFunction(): callable
    {
        return fn ($a, $b): int => spl_object_id($a) <=> spl_object_id($b);
    }
}
