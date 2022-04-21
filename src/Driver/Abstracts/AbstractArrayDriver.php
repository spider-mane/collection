<?php

namespace WebTheory\Collection\Driver\Abstracts;

use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;

abstract class AbstractArrayDriver implements ArrayDriverInterface
{
    protected ObjectComparatorInterface $objectComparator;

    public function __construct(ObjectComparatorInterface $objectComparator)
    {
        $this->objectComparator = $objectComparator;
    }

    public function retrieve(array $array, $item)
    {
        return $array[$item];
    }

    public function remove(array &$array, $item): bool
    {
        if (is_object($item)) {
            return $this->deleteObjectIfLocated($array, $item);
        }

        if (isset($array[$item])) {
            unset($array[$item]);

            return true;
        }

        return false;
    }

    public function contains(array $array, $item): bool
    {
        return is_object($item)
            ? in_array($item, $array, true)
            : isset($array[$item]);
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
