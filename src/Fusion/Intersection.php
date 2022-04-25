<?php

namespace WebTheory\Collection\Fusion;

use WebTheory\Collection\Contracts\ArrayFusionInterface;
use WebTheory\Collection\Fusion\Abstracts\AbstractArrayFusion;

class Intersection extends AbstractArrayFusion implements ArrayFusionInterface
{
    public function remix(array ...$collections): array
    {
        $fusion = array_shift($collections);
        $callback = $this->getComparisonCallback();

        foreach ($collections as $collection) {
            $fusion = array_uintersect($fusion, $collection, $callback);
        }

        return $fusion;
    }
}
