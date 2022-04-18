<?php

namespace WebTheory\Collection\Sorting;

use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Sorting\Abstracts\AbstractSorter;

class CallbackBasedSorter extends AbstractSorter implements CollectionSorterInterface
{
    /**
     * @var callable
     */
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    protected function getSortingFunction(string $order): callable
    {
        return fn ($a, $b) => ($this->callback)($a, $b, $order);
    }
}
