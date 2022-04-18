<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractCollectionComparator;
use WebTheory\Collection\Comparison\Abstracts\UsesObjectComparatorTrait;
use WebTheory\Collection\Contracts\CollectionComparatorInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;

class RuntimeIdBasedCollectionComparator extends AbstractCollectionComparator implements CollectionComparatorInterface
{
    use UsesObjectComparatorTrait;

    protected function getObjectComparator(): ObjectComparatorInterface
    {
        return new RuntimeIdBasedObjectComparator();
    }
}
