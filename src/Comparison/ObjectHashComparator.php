<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractObjectComparator;

class ObjectHashComparator extends AbstractObjectComparator
{
    protected function getComparisonFunction(): callable
    {
        return fn ($a, $b): int => spl_object_hash($a) <=> spl_object_hash($b);
    }
}
