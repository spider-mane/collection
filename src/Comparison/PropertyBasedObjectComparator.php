<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractPropertyBasedObjectComparator;

class PropertyBasedObjectComparator extends AbstractPropertyBasedObjectComparator
{
    public function getComparisonFunction(): callable
    {
        return fn (
            $a,
            $b
        ): int => $this->resolveValue($a) <=> $this->resolveValue($b);
    }
}
