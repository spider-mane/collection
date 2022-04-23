<?php

namespace WebTheory\Collection\Sorting;

use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;

class MapBasedSorter extends PropertyBasedSorter implements CollectionSorterInterface
{
    protected array $map = [];

    public function __construct(PropertyResolverInterface $resolver, string $property, array $map)
    {
        parent::__construct($resolver, $property);

        $this->map = $map;
    }

    protected function resolveValue($item): int
    {
        return $this->map[parent::resolveValue($item)] ?? 0;
    }
}
