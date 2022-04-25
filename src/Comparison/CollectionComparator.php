<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractCollectionComparator;
use WebTheory\Collection\Comparison\Abstracts\UsesObjectComparatorTrait;
use WebTheory\Collection\Contracts\CollectionComparatorInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;

class CollectionComparator extends AbstractCollectionComparator implements CollectionComparatorInterface
{
    use UsesObjectComparatorTrait;

    protected ObjectComparatorInterface $objectComparator;

    public function __construct(ObjectComparatorInterface $objectComparator)
    {
        $this->objectComparator = $objectComparator;
    }

    protected function getObjectComparator(): ObjectComparatorInterface
    {
        return $this->objectComparator;
    }
}
