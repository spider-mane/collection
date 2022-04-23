<?php

namespace WebTheory\Collection\Driver;

use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Driver\Abstracts\AbstractArrayDriver;

class StandardList extends AbstractArrayDriver implements ArrayDriverInterface
{
    public function insertItem(array &$array, object $item, $offset = null): void
    {
        $array[] = $item;
    }
}
