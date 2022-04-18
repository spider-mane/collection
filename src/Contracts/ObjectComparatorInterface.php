<?php

namespace WebTheory\Collection\Contracts;

interface ObjectComparatorInterface
{
    public function comparison(object $object1, object $object2): int;

    public function matches(object $object1, object $object2): bool;

    public function getComparisonFunction(): callable;
}
