<?php

namespace WebTheory\Collection\Sorting;

use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Sorting\Abstracts\AbstractPropertyBasedSorter;

class PropertyBasedSorter extends AbstractPropertyBasedSorter implements CollectionSorterInterface
{
    protected function getSortingFunction(string $order): callable
    {
        return fn ($a, $b): int => $this->resolveEntriesOrder(
            $this->resolveValue($a),
            $this->resolveValue($b),
            $order
        );
    }
}
