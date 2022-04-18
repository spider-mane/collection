<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractCollectionComparator;
use WebTheory\Collection\Comparison\Abstracts\UsesObjectComparatorTrait;
use WebTheory\Collection\Contracts\CollectionComparatorInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;

class ObjectComparatorBasedCollectionComparator extends AbstractCollectionComparator implements CollectionComparatorInterface
{
    use UsesObjectComparatorTrait;

    protected ObjectComparatorInterface $comparator;

    public function __construct(ObjectComparatorInterface $comparator)
    {
        $this->comparator = $comparator;
    }

    protected function getObjectComparator(): ObjectComparatorInterface
    {
        return $this->comparator;
    }
}
