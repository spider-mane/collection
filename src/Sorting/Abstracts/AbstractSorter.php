<?php

namespace WebTheory\Collection\Sorting\Abstracts;

use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Enum\Order;

abstract class AbstractSorter implements CollectionSorterInterface
{
    public function sort(array $array, string $order): array
    {
        $this->validateOrder($order);

        return $this->getSortedArray($array, $order);
    }

    protected function getSortedArray(array $array, string $order): array
    {
        $function = $this->getSortingFunction($order);

        if (array_is_list($array)) {
            usort($array, $function);
        } else {
            uasort($array, $function);
        }

        return $array;
    }

    protected function validateOrder($order): void
    {
        Order::throwExceptionIfInvalid($order);
    }

    protected function getSortingFunction(string $order): callable
    {
        return fn ($a, $b) => $this->doSortingOperation($a, $b, $order);
    }

    protected function doSortingOperation(object $a, object $b, string $order): int
    {
        return $this->compare($a, $b) * ($order === Order::Desc ? -1 : 1);
    }

    abstract protected function compare(object $a, object $b): int;
}
