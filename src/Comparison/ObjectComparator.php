<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractObjectComparator;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;

class ObjectComparator extends AbstractObjectComparator implements ObjectComparatorInterface
{
    protected function getComparisonFunction(): callable
    {
        return fn (object $a, object $b): int => $a <=> $b;
    }
}
