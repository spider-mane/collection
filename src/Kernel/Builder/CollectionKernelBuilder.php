<?php

namespace WebTheory\Collection\Kernel\Builder;

use Closure;
use WebTheory\Collection\Contracts\JsonSerializerInterface;
use WebTheory\Collection\Kernel\CollectionKernel;
use WebTheory\Collection\Resolution\PropertyResolver;

class CollectionKernelBuilder
{
    protected array $items = [];

    protected Closure $factory;

    protected array $accessors = [];

    protected ?string $identifier = null;

    protected bool $mapToIdentifier = false;

    protected JsonSerializerInterface $jsonSerializer;

    protected PropertyResolver $propertyResolver;

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
        $this->mapToIdentifier = $map;

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
            $this->mapToIdentifier,
            $this->jsonSerializer,
        );
    }
}
