<?php

namespace WebTheory\Collection\Driver;

use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Contracts\CollectionQueryInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Driver\Abstracts\AbstractArrayDriver;
use WebTheory\Collection\Query\BasicQuery;
use WebTheory\Collection\Resolution\Abstracts\ResolvesPropertyValueTrait;

class IdentifiableItemList extends AbstractArrayDriver implements ArrayDriverInterface
{
    use ResolvesPropertyValueTrait;

    public function __construct(
        string $property,
        PropertyResolverInterface $propertyResolver,
        ObjectComparatorInterface $objectComparator
    ) {
        $this->property = $property;
        $this->propertyResolver = $propertyResolver;

        parent::__construct($objectComparator);
    }

    public function insert(array &$array, object $item, $locator = null): bool
    {
        if ($this->arrayContainsObject($array, $item)) {
            return false;
        }

        $array[] = $item;

        return true;
    }

    public function remove(array &$array, $item): bool
    {
        if (is_object($item)) {
            return $this->deleteObjectIfLocated($array, $item);
        }

        if ($item = $this->getFirstMatchingItem($array, $item)) {
            return $this->remove($array, $item);
        }

        return false;
    }

    public function contains(array $array, $item): bool
    {
        if (is_object($item)) {
            return $this->arrayContainsObject($array, $item);
        }

        return !empty($this->getMatchingItems($array, $item));
    }

    protected function getMatchingItems(array $array, $item): array
    {
        return $this->getQuery($this->property, '=', $item)->query($array);
    }

    protected function getFirstMatchingItem(array $array, $item): ?object
    {
        return $this->getMatchingItems($array, $item)[0] ?? null;
    }

    protected function getQuery(string $property, string $operator, $value): CollectionQueryInterface
    {
        return new BasicQuery(
            $this->propertyResolver,
            $property,
            $operator,
            $value,
        );
    }
}
