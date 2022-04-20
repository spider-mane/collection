<?php

namespace WebTheory\Collection\Contracts;

interface LoopInterface
{
    public function iterate(iterable $items, callable $callback): void;
}
