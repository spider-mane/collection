<?php

namespace WebTheory\Collection\Kernel\Builder;

use Closure;
use WebTheory\Collection\Contracts\JsonSerializerInterface;
use WebTheory\Collection\Kernel\CollectionKernel;

class CollectionKernelBuilder
{
    protected array $items = [];

    protected Closure $generator;

    protected array $accessors = [];

    protected ?string $identifier = null;

    protected bool $isMap = false;

    protected ?JsonSerializerInterface $jsonSerializer = null;

    public function withItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function withGenerator(Closure $generator): self
    {
        $this->generator = $generator;

        return $this;
    }

    public function withAccessors(array $accessors): self
    {
        $this->accessors = $accessors;

        return $this;
    }

    public function withIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function thatIsMapped(): self
    {
        $this->isMap = true;

        return $this;
    }

    public function thatIsNotMapped(): self
    {
        $this->isMap = false;

        return $this;
    }

    public function withMapped(bool $isMap): self
    {
        $this->isMap = $isMap;

        return $this;
    }

    public function withJsonSerializer(JsonSerializerInterface $jsonSerializer): self
    {
        $this->jsonSerializer = $jsonSerializer;

        return $this;
    }

    public function build(): CollectionKernel
    {
        return new CollectionKernel(
            $this->items,
            $this->generator,
            $this->identifier,
            $this->accessors,
            $this->isMap,
            $this->jsonSerializer,
        );
    }
}
