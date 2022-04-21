<?php

namespace WebTheory\Collection\Comparison\Abstracts;

use WebTheory\Collection\Contracts\ObjectComparatorInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Resolution\Abstracts\ResolvesPropertyValueTrait;

abstract class AbstractPropertyBasedObjectComparator extends AbstractObjectComparator implements ObjectComparatorInterface
{
    use ResolvesPropertyValueTrait;

    public function __construct(PropertyResolverInterface $propertyResolver, string $property)
    {
        $this->propertyResolver = $propertyResolver;
        $this->property = $property;
    }
}
