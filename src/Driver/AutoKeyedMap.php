<?php

namespace WebTheory\Collection\Driver;

use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Driver\Abstracts\AbstractArrayDriver;
use WebTheory\Collection\Resolution\Abstracts\ResolvesPropertyValueTrait;

class AutoKeyedMap extends AbstractArrayDriver implements ArrayDriverInterface
{
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

    public function insertItem(array &$array, object $item, $offset = null): void
    {
        $array[$this->resolveValue($item)] = $item;
    }
}
