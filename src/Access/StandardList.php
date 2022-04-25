<?php

namespace WebTheory\Collection\Access;

use WebTheory\Collection\Access\Abstracts\AbstractArrayDriver;
use WebTheory\Collection\Access\Abstracts\RejectsDuplicateObjectsTrait;
use WebTheory\Collection\Contracts\ArrayDriverInterface;

class StandardList extends AbstractArrayDriver implements ArrayDriverInterface
{
    use RejectsDuplicateObjectsTrait;

    public function append(array &$array, object $item, $offset = null): void
    {
        $array[] = $item;
    }
}
