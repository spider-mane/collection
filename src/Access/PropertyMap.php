<?php

namespace WebTheory\Collection\Access;

use WebTheory\Collection\Access\Abstracts\AbstractArrayDriver;
use WebTheory\Collection\Access\Abstracts\AutoKeyedMapTrait;
use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Resolution\Abstracts\ResolvesPropertyValueTrait;

class PropertyMap extends AbstractArrayDriver implements ArrayDriverInterface
{
    use AutoKeyedMapTrait;
    use ResolvesPropertyValueTrait;

    public function __construct(
        string $property,
        PropertyResolverInterface $propertyResolver,
        ObjectComparatorInterface $objectComparator
    ) {
        $this->property = $property;
        $this->propertyResolver = $propertyResolver;
        $this->objectComparator = $objectComparator;
    }

    protected function getObjectAsKey(object $item): string
    {
        return $this->resolveValue($item);
    }
}
