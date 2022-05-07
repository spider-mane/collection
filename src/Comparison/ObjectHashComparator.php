<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractObjectComparator;

class ObjectHashComparator extends AbstractObjectComparator
{
    public function comparison(object $a, object $b): int
    {
        return spl_object_hash($a) <=> spl_object_hash($b);
    }
}
