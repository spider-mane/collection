<?php

namespace WebTheory\Collection\Fusion\Abstracts;

use WebTheory\Collection\Contracts\ArrayFusionInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;

abstract class AbstractArrayFusion implements ArrayFusionInterface
{
    protected ObjectComparatorInterface $objectComparator;

    public function __construct(ObjectComparatorInterface $objectComparator)
    {
        $this->objectComparator = $objectComparator;
    }

    protected function getComparisonCallback(): callable
    {
        return [$this->objectComparator, 'comparison'];
    }
}
