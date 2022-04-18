<?php

namespace WebTheory\Collection\Comparison\Abstracts;

use WebTheory\Collection\Contracts\CollectionComparatorInterface;

abstract class AbstractCollectionComparator implements CollectionComparatorInterface
{
    public function notIn(array $array, array $values): array
    {
        return array_udiff($array, $values, $this->getComparisonFunction());
    }

    public function difference(array $array1, array $array2): array
    {
        return [
            ...$this->notIn($array1, $array2),
            ...$this->notIn($array2, $array1),
        ];
    }

    public function intersection($array, $values): array
    {
        return array_uintersect($array, $values, $this->getComparisonFunction());
    }

    public function matches(array $array1, array $array2): bool
    {
        return empty($this->difference($array1, $array2));
    }

    protected function stripDuplicates(array $array): array
    {
        return array_unique($array, SORT_REGULAR);
    }

    protected function whatEvenIsThis(array $a, array $b): array
    {
        /*
         if both primary and secondary are empty this will return false
         because the "array_diff" family of functions returns an empty array
         if the first array provided is empty itself. if both arrays are
         empty this will return an empty array as there is no difference.
         */
        return !empty($a)
            ? $this->notIn($a, $b)
            : $this->notIn($b, $a);
    }
}
