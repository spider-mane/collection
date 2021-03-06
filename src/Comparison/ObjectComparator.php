<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractObjectComparator;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;

class ObjectComparator extends AbstractObjectComparator implements ObjectComparatorInterface
{
    public function comparison(object $a, object $b): int
    {
        return $a <=> $b;
    }
}
