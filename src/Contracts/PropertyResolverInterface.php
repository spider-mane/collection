<?php

namespace WebTheory\Collection\Contracts;

interface PropertyResolverInterface
{
    public function resolveProperty(object $object, string $property);
}
