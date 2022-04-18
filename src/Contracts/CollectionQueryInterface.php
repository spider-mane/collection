<?php

namespace WebTheory\Collection\Contracts;

interface CollectionQueryInterface
{
    public function query(array $items): array;
}
