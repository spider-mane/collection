<?php

namespace WebTheory\Collection\Access;

use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Contracts\CollectionQueryInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Query\BasicQuery;
use WebTheory\Collection\Resolution\Abstracts\ResolvesPropertyValueTrait;

class IdentifiableItemList extends StandardList implements ArrayDriverInterface
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

    public function fetch(array $array, $item)
    {
        return $this->getFirstMatchingItem($array, $item);
    }

    public function maybeRemoveItem(array &$array, $item): bool
    {
        return ($item = $this->getFirstMatchingItem($array, $item))
            ? $this->remove($array, $item)
            : false;
    }

    protected function arrayContains(array $array, $item): bool
    {
        return $this->getQuery(...$this->query($item))->match($array);
    }

    protected function getFirstMatchingItem(array $array, $item): ?object
    {
        return $this->getQuery(...$this->query($item))->first($array);
    }

    protected function query($item): array
    {
        return [$this->property, '=', $item];
    }

    protected function getQuery(string $property, string $operator, $value): CollectionQueryInterface
    {
        return new BasicQuery(
            $property,
            $operator,
            $value,
            $this->propertyResolver,
        );
    }
}
