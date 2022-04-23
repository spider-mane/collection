<?php

namespace WebTheory\Collection\Comparison\Abstracts;

use LogicException;
use WebTheory\Collection\Contracts\CollectionComparatorInterface;

abstract class AbstractCollectionComparator implements CollectionComparatorInterface
{
    public function diff(array $array, array $values): array
    {
        return array_udiff($array, $values, $this->getComparisonFunction());
    }

    public function contrast(array $array1, array $array2): array
    {
        return array_merge(
            $this->diff($array1, $array2),
            $this->diff($array2, $array1),
        );
    }

    public function intersect($array, $values): array
    {
        return array_uintersect($array, $values, $this->getComparisonFunction());
    }

    public function matches(array $array1, array $array2): bool
    {
        return empty($this->contrast($array1, $array2));
    }

    protected function whatEvenIsThis(array $array1, array $array2): array
    {
        throw new LogicException('No!');

        /*
         if both primary and secondary are empty this will return false
         because the "array_diff" family of functions returns an empty array
         if the first array provided is empty itself. if both arrays are
         empty this will return an empty array as there is no difference.
         */
        return !empty($array1)
            ? $this->diff($array1, $array2)
            : $this->diff($array2, $array1);
    }
}
