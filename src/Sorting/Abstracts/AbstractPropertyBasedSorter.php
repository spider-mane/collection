<?php

namespace WebTheory\Collection\Sorting\Abstracts;

use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Resolution\Abstracts\ResolvesPropertyValueTrait;

abstract class AbstractPropertyBasedSorter extends AbstractSorter
{
    use ResolvesPropertyValueTrait;

    protected string $property;

    public function __construct(PropertyResolverInterface $resolver)
    {
        $this->propertyResolver = $resolver;
    }

    public function setProperty(string $property): AbstractPropertyBasedSorter
    {
        $this->property = $property;

        return $this;
    }
}
