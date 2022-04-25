<?php

namespace WebTheory\Collection\Fusion;

use WebTheory\Collection\Contracts\ArrayFusionInterface;

class Merger implements ArrayFusionInterface
{
    public function remix(array ...$collections): array
    {
        return array_unique(array_merge(...$collections), SORT_REGULAR);
    }
}
