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

    /**
     * @return $this
     */
    public function withItems(array $items): CollectionKernelBuilder
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @return $this
     */
    public function withGenerator(Closure $generator): CollectionKernelBuilder
    {
        $this->generator = $generator;

        return $this;
    }

    /**
     * @return $this
     */
    public function withAccessors(array $accessors): CollectionKernelBuilder
    {
        $this->accessors = $accessors;

        return $this;
    }

    /**
     * @return $this
     */
    public function withIdentifier(?string $identifier): CollectionKernelBuilder
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return $this
     */
    public function thatIsMapped(): CollectionKernelBuilder
    {
        $this->isMap = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function thatIsNotMapped(): CollectionKernelBuilder
    {
        $this->isMap = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function withMapped(bool $isMap): CollectionKernelBuilder
    {
        $this->isMap = $isMap;

        return $this;
    }

    /**
     * @return $this
     */
    public function withJsonSerializer(JsonSerializerInterface $jsonSerializer): CollectionKernelBuilder
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
