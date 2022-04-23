<?php

namespace WebTheory\Collection\Sorting\Abstracts;

use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Sorting\Order;

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

    protected function getSortingFunction(string $order): callable
    {
        return fn ($a, $b): int => $this->resolveEntriesOrder(
            $this->resolveValue($a),
            $this->resolveValue($b),
            $order
        );
    }

    protected function resolveEntriesOrder($a, $b, string $order): int
    {
        return ($a <=> $b) * ($order === Order::DESC ? -1 : 1);
    }

    protected function validateOrder($order): void
    {
        Order::throwExceptionIfInvalid($order);
    }

    abstract protected function resolveValue(object $object);
}
