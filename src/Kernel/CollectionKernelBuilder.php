<?php

namespace WebTheory\Collection\Kernel;

use WebTheory\Collection\Contracts\JsonSerializerInterface;
use WebTheory\Collection\Resolution\PropertyResolver;

class CollectionKernelBuilder
{
    protected array $items = [];

    /**
     * @var callable
     */
    protected $factory;

    protected ?string $identifier = null;

    protected bool $map = false;

    protected JsonSerializerInterface $jsonSerializer;

    protected PropertyResolver $propertyResolver;

    protected array $accessors = [];

    public function withItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function withFactory(callable $factory): self
    {
        $this->factory = $factory;

        return $this;
    }

    public function withAccessors(array $accessors): self
    {
        $this->accessors = $accessors;

        return $this;
    }

    public function withIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function withMapped(bool $map): self
    {
        $this->map = $map;

        return $this;
    }

    public function withJsonSerializer(JsonSerializerInterface $jsonSerializer): self
    {
        $this->jsonSerializer = $jsonSerializer;

        return $this;
    }

    public function withPropertyResolver(PropertyResolver $propertyResolver): self
    {
        $this->propertyResolver = $propertyResolver;

        return $this;
    }

    public function build(): CollectionKernel
    {
        return new CollectionKernel(
            $this->items,
            $this->factory,
            $this->identifier,
            $this->accessors,
            $this->map,
            $this->jsonSerializer,
        );
    }
}
