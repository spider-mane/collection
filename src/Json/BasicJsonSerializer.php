<?php

namespace WebTheory\Collection\Json;

use WebTheory\Collection\Contracts\JsonSerializerInterface;

class BasicJsonSerializer implements JsonSerializerInterface
{
    public function serialize(array $items): string
    {
        return json_encode($items, JSON_THROW_ON_ERROR);
    }
}
