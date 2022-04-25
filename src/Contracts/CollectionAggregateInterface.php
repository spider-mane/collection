<?php

namespace WebTheory\Collection\Contracts;

interface CollectionAggregateInterface
{
    public function operate(array ...$collections);
}
