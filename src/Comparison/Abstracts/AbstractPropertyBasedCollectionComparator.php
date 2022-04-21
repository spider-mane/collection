<?php

namespace WebTheory\Collection\Comparison\Abstracts;

use WebTheory\Collection\Contracts\CollectionComparatorInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Resolution\Abstracts\ResolvesPropertyValueTrait;

abstract class AbstractPropertyBasedCollectionComparator extends AbstractCollectionComparator implements CollectionComparatorInterface
{
    use ResolvesPropertyValueTrait;

    public function __construct(PropertyResolverInterface $propertyResolver, string $property)
    {
        $this->propertyResolver = $propertyResolver;
        $this->property = $property;
    }
}
