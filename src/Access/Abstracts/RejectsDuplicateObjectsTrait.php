<?php

namespace WebTheory\Collection\Access\Abstracts;

trait RejectsDuplicateObjectsTrait
{
    public function insert(array &$array, object $item, $offset = null): bool
    {
        if ($this->arrayContainsObject($array, $item)) {
            return false;
        }

        $this->append($array, $item, $offset);

        return true;
    }

    abstract protected function arrayContainsObject(array $array, object $object): bool;

    abstract protected function append(array &$array, object $item, $offset = null): void;
}
