<?php

namespace WebTheory\Collection\Sorting;

use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Contracts\OrderInterface;
use WebTheory\Collection\Sorting\Abstracts\AbstractPropertyBasedSorter;

class MapBasedSorter extends AbstractPropertyBasedSorter implements CollectionSorterInterface
{
    protected array $map = [];

    public function setMap(array $map): MapBasedSorter
    {
        $this->map = $map;

        return $this;
    }

    public function setProperty(string $property): MapBasedSorter
    {
        return parent::setProperty($property);
    }

    protected function getSortingFunction(string $order = OrderInterface::ASC): callable
    {
        return fn ($a, $b): int => $this->resolveEntriesOrder(
            $this->getComparisonValueFor($a),
            $this->getComparisonValueFor($b),
            $order
        );
    }

    protected function getComparisonValueFor($item): int
    {
        return (int) $this->map[$this->resolveValue($item)] ?? 0;
    }
}
