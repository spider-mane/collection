<?php

namespace WebTheory\Collection\Resolution\Abstracts;

use WebTheory\Collection\Contracts\PropertyResolverInterface;

trait ResolvesPropertyValueTrait
{
    protected PropertyResolverInterface $propertyResolver;

    protected string $property;

    protected function resolveValue(object $object)
    {
        return $this->propertyResolver->resolveProperty($object, $this->property);
    }
}
