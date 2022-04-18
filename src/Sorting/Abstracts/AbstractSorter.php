<?php

namespace WebTheory\Collection\Sorting\Abstracts;

use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Contracts\OrderInterface;
use WebTheory\Collection\Sorting\Order;

abstract class AbstractSorter implements CollectionSorterInterface
{
    public function sort(array $array, string $order): array
    {
        $this->validateOrder($order);

        $array = $array;

        usort($array, $this->getSortingFunction($order));

        return $array;
    }

    public function ksort(array $array, string $order): array
    {
        $this->validateOrder($order);

        $array = $array;

        uksort($array, $this->getSortingFunction($order));

        return $array;
    }

    protected function resolveEntriesOrder($a, $b, string $order): int
    {
        return ($a <=> $b) * ($order === OrderInterface::DESC ? -1 : 1);
    }

    protected function validateOrder($order): void
    {
        Order::throwExceptionIfInvalid($order);
    }

    abstract protected function getSortingFunction(string $order): callable;
}
