<?php

namespace WebTheory\Collection\Kernel\Factory;

use WebTheory\Collection\Access\IdentifiableItemList;
use WebTheory\Collection\Access\PropertyMap;
use WebTheory\Collection\Access\StandardList;
use WebTheory\Collection\Access\StandardMap;
use WebTheory\Collection\Comparison\CollectionComparator;
use WebTheory\Collection\Comparison\ObjectComparator;
use WebTheory\Collection\Comparison\ObjectPropertyComparator;
use WebTheory\Collection\Contracts\ArrayDriverInterface;
use WebTheory\Collection\Contracts\CollectionComparatorInterface;
use WebTheory\Collection\Contracts\ObjectComparatorInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
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
            ? new ObjectPropertyComparator($this->propertyResolver, $identifier)
            : new ObjectComparator();
    }

    public function getPropertyResolver(): PropertyResolverInterface
    {
        return $this->propertyResolver;
    }

    public function getObjectComparator(): ObjectComparatorInterface
    {
        return $this->objectComparator;
    }

    public function getCollectionComparator(): CollectionComparatorInterface
    {
        return new CollectionComparator($this->objectComparator);
    }

    public function getArrayDriver(): ArrayDriverInterface
    {
        if ($this->identifier && $this->isMap) {
            $driver = new PropertyMap(
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
