<?php

namespace WebTheory\Collection\Comparison\Abstracts;

use WebTheory\Collection\Contracts\CollectionComparatorInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Resolution\Abstracts\ResolvesPropertyValueTrait;

abstract class AbstractPropertyBasedCollectionComparator extends AbstractCollectionComparator implements CollectionComparatorInterface
{
    use ResolvesPropertyValueTrait;

    public function __construct(PropertyResolverInterface $propertyResolver)
    {
        $this->propertyResolver = $propertyResolver;
    }

    public function setProperty(string $property): AbstractPropertyBasedCollectionComparator
    {
        $this->property = $property;

        return $this;
    }
}
