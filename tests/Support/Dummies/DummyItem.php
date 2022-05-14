<?php

namespace Tests\Support\Dummies;

/**
 * @property string $id
 */
class DummyItem
{
    protected $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function getId(): string
    {
        return $this->property;
    }
}
