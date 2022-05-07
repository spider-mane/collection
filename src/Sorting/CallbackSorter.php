<?php

namespace WebTheory\Collection\Sorting;

use WebTheory\Collection\Contracts\CollectionSorterInterface;
use WebTheory\Collection\Sorting\Abstracts\AbstractSorter;

class CallbackSorter extends AbstractSorter implements CollectionSorterInterface
{
    /**
     * @var callable
     */
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    protected function compare(object $a, object $b): int
    {
        return ($this->callback)($a, $b);
    }
}
