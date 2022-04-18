<?php

namespace WebTheory\Collection\Resolution;

use LogicException;
use WebTheory\Collection\Contracts\PropertyResolverInterface;

class PropertyResolver implements PropertyResolverInterface
{
    protected array $accessors = [];

    public function __construct(array $accessors = [])
    {
        $this->accessors = $accessors;
    }

    public function resolveProperty(object $object, string $property)
    {
        $accessor = $this->resolvePropertyAccessor($property);

        if ($accessor && method_exists($object, $accessor)) {
            return $object->{$accessor}();
        }

        if (property_exists($object, $property)) {
            return $object->$property;
        }

        throw new LogicException(
            sprintf(
                'No method of access for "%s" in %s has been defined.',
                $property,
                get_class($object)
            )
        );
    }

    protected function resolvePropertyAccessor(string $property): ?string
    {
        return $this->accessors[$property] ?? null;
    }
}
