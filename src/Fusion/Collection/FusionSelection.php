<?php

namespace WebTheory\Collection\Fusion\Collection;

use WebTheory\Collection\Access\StandardMap;
use WebTheory\Collection\Comparison\ObjectComparator;
use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Contracts\ArrayFusionInterface;

class FusionSelection
{
    /**
     * @var array<string,ArrayFusionInterface>
     */
    protected array $fusions = [];

    protected ArrayDriverInterface $driver;

    public function __construct(array $fusions)
    {
        $this->driver = new StandardMap(new ObjectComparator());

        $this->collect($fusions);
    }

    public function collect(array $fusions)
    {
        array_walk($fusions, [$this, 'insert']);
    }

    public function insert(ArrayFusionInterface $fusion, string $name): void
    {
        $this->driver->insert($this->fusions, $fusion, $name);
    }

    public function remove(string $fusion): void
    {
        $this->driver->remove($this->fusions, $fusion);
    }

    public function fetch(string $fusion): ArrayFusionInterface
    {
        return $this->driver->fetch($this->fusions, $fusion);
    }

    public function contains(string $fusion): bool
    {
        return $this->driver->contains($this->fusions, $fusion);
    }

    public function remix(string $fusion, array ...$collections): array
    {
        return $this->fetch($fusion)->remix(...$collections);
    }
}
