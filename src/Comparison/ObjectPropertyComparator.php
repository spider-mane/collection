<?php

namespace WebTheory\Collection\Comparison;

use WebTheory\Collection\Comparison\Abstracts\AbstractObjectComparator;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Resolution\Abstracts\ResolvesPropertyValueTrait;

class ObjectPropertyComparator extends AbstractObjectComparator implements ObjectComparatorInterface
{
    use ResolvesPropertyValueTrait;

    public function __construct(PropertyResolverInterface $propertyResolver, string $property)
    {
        $this->propertyResolver = $propertyResolver;
        $this->property = $property;
    }

    public function comparison(object $a, object $b): int
    {
        return $this->resolveValue($a) <=> $this->resolveValue($b);
    }
}
