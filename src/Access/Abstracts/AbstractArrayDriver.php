<?php

namespace WebTheory\Collection\Access\Abstracts;

use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;

abstract class AbstractArrayDriver implements ArrayDriverInterface
{
    protected ObjectComparatorInterface $objectComparator;

    public function __construct(ObjectComparatorInterface $objectComparator)
    {
        $this->objectComparator = $objectComparator;
    }

    public function fetch(array $array, $item)
    {
        return $array[$item];
    }

    public function collect(array &$array, array $items): void
    {
        foreach ($items as $offset => $item) {
            $this->insert($array, $item, $offset);
        }
    }

    public function remove(array &$array, $item): bool
    {
        return is_object($item)
            ? $this->deleteObjectIfLocated($array, $item)
            : $this->maybeRemoveItem($array, $item);
    }

    public function contains(array $array, $item): bool
    {
        return is_object($item)
            ? $this->arrayContainsObject($array, $item)
            : $this->arrayContains($array, $item);
    }

    protected function maybeRemoveItem(array &$array, $item): bool
    {
        if (isset($array[$item])) {
            unset($array[$item]);

            return true;
        }

        return false;
    }

    protected function arrayContains(array $array, $item): bool
    {
        return isset($array[$item]);
    }

    protected function arrayContainsObject(array $array, object $object): bool
    {
        foreach ($array as $item) {
            if ($this->objectComparator->matches($item, $object)) {
                return true;
            }
        }

        return false;
    }

    protected function deleteObjectIfLocated(array &$array, object $object): bool
    {
        $position = array_search($object, $array, true);

        if ($position === false) {
            return false;
        }

        unset($array[$position]);

        return true;
    }
}
