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

    protected function getComparisonFunction(): callable
    {
        return fn (
            $a,
            $b
        ): int => $this->resolveValue($a) <=> $this->resolveValue($b);
    }
}
