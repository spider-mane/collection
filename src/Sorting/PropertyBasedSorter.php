<?php

namespace WebTheory\Collection\Sorting;

use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Resolution\Abstracts\ResolvesPropertyValueTrait;
use WebTheory\Collection\Sorting\Abstracts\AbstractSorter;

class PropertyBasedSorter extends AbstractSorter implements CollectionSorterInterface
{
    use ResolvesPropertyValueTrait;

    public function __construct(PropertyResolverInterface $resolver, string $property)
    {
        $this->propertyResolver = $resolver;
        $this->property = $property;
    }
}
