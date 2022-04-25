<?php

namespace WebTheory\Collection\Contracts;

interface ArrayFusionInterface
{
    public function remix(array ...$collections): array;
}
