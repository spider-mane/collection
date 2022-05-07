<?php

namespace WebTheory\Collection\Resolution;

use ErrorException;
use LogicException;
use WebTheory\Collection\Contracts\PropertyResolverInterface;

class PropertyResolver implements PropertyResolverInterface
{
    protected array $defined = [];

    protected array $methods = [];

    protected array $members = [];

    public function __construct(array $defined = [])
    {
        $this->defined = $defined;
    }

    public function resolveProperty(object $object, string $property)
    {
        if ($definedMethod = $this->getDefinedMethod($property)) {
            return $object->{$definedMethod}();
        }

        if (property_exists($object, $property)) {
            try {
                return $object->{$property};
            } catch (ErrorException $e) {
                // move on
            }
        }

        $inferredMethod = $this->getInferredMethod($property);

        if (is_callable([$object, $inferredMethod])) {
            return $object->{$inferredMethod}();
        }

        $inferredMember = $this->getInferredMember($property);

        if (property_exists($object, $inferredMember)) {
            try {
                return $object->{$inferredMember};
            } catch (ErrorException $e) {
                // move on
            }
        }

        if (is_callable([$object, $property])) {
            try {
                return $object->{$property}();
            } catch (ErrorException $e) {
                // move on
            }
        }

        throw new LogicException(
            sprintf(
                'No method of access has been defined or can be resolved for value "%s" in instances of class %s.',
                $property,
                get_class($object)
            )
        );
    }

    protected function getDefinedMethod(string $property): ?string
    {
        return $this->defined[$property] ?? null;
    }

    protected function getInferredMethod(string $property): string
    {
        return $this->methods[$property]
            ??= $this->getPropertyAsMethod($property);
    }

    protected function getInferredMember(string $property): string
    {
        return $this->members[$property]
            ??= $this->getPropertyAsMember($property);
    }

    protected function getPropertyAsMethod(string $property): string
    {
        return 'get' . $this->convertToStudlyCaps($property);
    }

    protected function getPropertyAsMember(string $property): string
    {
        return lcfirst($this->convertToStudlyCaps($property));
    }

    protected function convertToStudlyCaps(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }
}
