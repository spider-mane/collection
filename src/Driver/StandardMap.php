<?php

namespace WebTheory\Collection\Driver;

use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Driver\Abstracts\AbstractArrayDriver;

class StandardMap extends AbstractArrayDriver implements ArrayDriverInterface
{
    public function insertItem(array &$array, object $item, $offset = null): void
    {
        $array[$offset] = $item;
    }
}
