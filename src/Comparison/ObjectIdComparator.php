<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractObjectComparator;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;

class ObjectIdComparator extends AbstractObjectComparator implements ObjectComparatorInterface
{
    public function comparison(object $a, object $b): int
    {
        return spl_object_id($a) <=> spl_object_id($b);
    }
}
