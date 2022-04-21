<?php

namespace WebTheory\Collection\Driver;

use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Driver\Abstracts\AbstractArrayDriver;

class StandardList extends AbstractArrayDriver implements ArrayDriverInterface
{
    public function insert(array &$array, object $item, $locator = null): bool
    {
        if ($this->arrayContainsObject($array, $item)) {
            return false;
        }

        $array[] = $item;

        return true;
    }
}
