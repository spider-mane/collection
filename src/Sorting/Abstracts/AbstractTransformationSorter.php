<?php

namespace WebTheory\Collection\Sorting\Abstracts;

use WebTheory\Collection\Contracts\CollectionSorterInterface;

abstract class AbstractTransformationSorter extends AbstractSorter implements CollectionSorterInterface
{
    protected function compare(object $a, object $b): int
    {
        return $this->resolveValue($a) <=> $this->resolveValue($b);
    }

    abstract protected function resolveValue(object $object);
}
