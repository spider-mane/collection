<?php

namespace WebTheory\Collection\Contracts;

interface ObjectComparatorInterface
{
    public function comparison(object $a, object $b): int;

    public function matches(object $a, object $b): bool;
}
