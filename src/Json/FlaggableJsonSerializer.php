<?php

namespace WebTheory\Collection\Json;

use WebTheory\Collection\Contracts\JsonSerializerInterface;

class FlaggableJsonSerializer implements JsonSerializerInterface
{
    protected int $flags;

    public function __construct(int $flags = 0)
    {
        $this->flags = $flags;
    }

    public function serialize(array $items): string
    {
        return json_encode($items, JSON_THROW_ON_ERROR | $this->flags);
    }
}
