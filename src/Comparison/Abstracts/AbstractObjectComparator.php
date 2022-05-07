<?php

namespace WebTheory\Collection\Comparison\Abstracts;

use WebTheory\Collection\Contracts\ObjectComparatorInterface;

abstract class AbstractObjectComparator implements ObjectComparatorInterface
{
    public function matches(object $a, object $b): bool
    {
        return $this->comparison($a, $b) === 0;
    }
}
