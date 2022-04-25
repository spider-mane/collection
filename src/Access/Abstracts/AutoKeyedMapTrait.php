<?php

namespace WebTheory\Collection\Access\Abstracts;

trait AutoKeyedMapTrait
{
    public function insert(array &$array, object $item, $offset = null): bool
    {
        $key = $this->getObjectAsKey($item);

        if (isset($array[$key])) {
            return false;
        }

        $array[$key] = $item;

        return true;
    }

    abstract protected function getObjectAsKey(object $item): string;
}
