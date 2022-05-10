<?php

namespace WebTheory\Collection\Contracts;

interface CollectionQueryInterface
{
    public function query(array $items): array;

    public function first(array $items): ?object;

    public function match(array $items): bool;
}
