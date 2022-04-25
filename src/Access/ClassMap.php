<?php

namespace WebTheory\Collection\Access;

use WebTheory\Collection\Access\Abstracts\AbstractArrayDriver;
use WebTheory\Collection\Access\Abstracts\AutoKeyedMapTrait;
use WebTheory\Collection\Contracts\ArrayDriverInterface;

class ClassMap extends AbstractArrayDriver implements ArrayDriverInterface
{
    use AutoKeyedMapTrait;

    protected function getObjectAsKey(object $item): string
    {
        return $this->convertToSnakeCase($this->getClassName($item));
    }

    protected function getClassName(object $class): string
    {
        $class = explode('\\', get_class($class));

        return end($class);
    }

    protected function convertToSnakeCase(string $class): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $class));
    }
}
