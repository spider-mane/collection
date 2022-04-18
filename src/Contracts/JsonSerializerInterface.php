<?php

namespace WebTheory\Collection\Contracts;

interface JsonSerializerInterface
{
    public function serialize(array $items): string;
}
