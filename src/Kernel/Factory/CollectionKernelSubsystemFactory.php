<?php

namespace WebTheory\Collection\Kernel\Factory;

use WebTheory\Collection\Comparison\PropertyBasedCollectionComparator;
use WebTheory\Collection\Comparison\PropertyBasedObjectComparator;
use WebTheory\Collection\Comparison\RuntimeIdBasedCollectionComparator;
use WebTheory\Collection\Comparison\RuntimeIdBasedObjectComparator;
use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Contracts\CollectionComparatorInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Driver\AutoKeyedMap;
use WebTheory\Collection\Driver\IdentifiableItemList;
use WebTheory\Collection\Driver\StandardList;
use WebTheory\Collection\Driver\StandardMap;
use WebTheory\Collection\Resolution\PropertyResolver;

class CollectionKernelSubsystemFactory
{
    protected ?string $identifier;

    protected array $accessors;

    protected bool $isMap;

    protected PropertyResolverInterface $propertyResolver;

    protected ObjectComparatorInterface $objectComparator;

    public function __construct(?string $identifier, array $accessors, bool $isMap)
    {
        $this->identifier = $identifier;
        $this->accessors = $accessors;
        $this->isMap = $isMap;

        $this->propertyResolver = new PropertyResolver($accessors);
        $this->objectComparator = $identifier
            ? new PropertyBasedObjectComparator($this->propertyResolver, $identifier)
            : new RuntimeIdBasedObjectComparator();
    }

    public function getPropertyResolver(): PropertyResolverInterface
    {
        return $this->propertyResolver;
    }

    public function getCollectionComparator(): CollectionComparatorInterface
    {
        return $this->identifier
            ? new PropertyBasedCollectionComparator($this->propertyResolver, $this->identifier)
            : new RuntimeIdBasedCollectionComparator();
    }

    public function getArrayDriver(): ArrayDriverInterface
    {
        if ($this->identifier && $this->isMap) {
            $driver = new AutoKeyedMap(
                $this->identifier,
                $this->propertyResolver,
                $this->objectComparator
            );
        } elseif ($this->identifier) {
            $driver = new IdentifiableItemList(
                $this->identifier,
                $this->propertyResolver,
                $this->objectComparator
            );
        } elseif ($this->isMap) {
            $driver = new StandardMap($this->objectComparator);
        } else {
            $driver = new StandardList($this->objectComparator);
        }

        return $driver;
    }
}
