<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractPropertyBasedCollectionComparator;
use WebTheory\Collection\Comparison\Abstracts\UsesObjectComparatorTrait;
use WebTheory\Collection\Contracts\CollectionComparatorInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;

class PropertyBasedCollectionComparator extends AbstractPropertyBasedCollectionComparator implements CollectionComparatorInterface
{
    use UsesObjectComparatorTrait;

    protected function getObjectComparator(): ObjectComparatorInterface
    {
        $comparator = new PropertyBasedObjectComparator($this->propertyResolver);
        $comparator->setProperty($this->property);

        return $comparator;
    }
}
