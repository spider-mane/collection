<?php

namespace WebTheory\Collection\Fusion;

use WebTheory\Collection\Contracts\ArrayFusionInterface;
use WebTheory\Collection\Fusion\Abstracts\AbstractArrayFusion;

class AltDiff extends AbstractArrayFusion implements ArrayFusionInterface
{
    /**
     * Creates a diff starting with the first non-empty array
     */
    public function remix(array ...$collections): array
    {
        $fusion = array_shift($collections);
        $callback = $this->getComparisonCallback();

        foreach ($collections as $collection) {
            $fusion = !empty($fusion)
                ? array_udiff($fusion, $collection, $callback)
                : array_udiff($collection, $fusion, $callback);
        }

        return $fusion;
    }
}
