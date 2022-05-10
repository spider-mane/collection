<?php

namespace WebTheory\Collection\Query;

use WebTheory\Collection\Contracts\CollectionQueryInterface;
use WebTheory\Collection\Contracts\OperationProviderInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Query\Operation\Operations;
use WebTheory\Collection\Resolution\Abstracts\ResolvesPropertyValueTrait;
use WebTheory\Collection\Resolution\PropertyResolver;

class BasicQuery implements CollectionQueryInterface
{
    use ResolvesPropertyValueTrait;

    protected OperationProviderInterface $operationProvider;

    protected string $operator;

    protected $value;

    public function __construct(
        string $property,
        string $operator,
        $value,
        ?PropertyResolverInterface $propertyResolver = null,
        ?OperationProviderInterface $operationProvider = null
    ) {
        $this->property = $property;
        $this->operator = $operator;
        $this->value = $value;

        $this->propertyResolver = $propertyResolver ?? new PropertyResolver();
        $this->operationProvider = $operationProvider ?? new Operations();
    }

    public function query(array $items): array
    {
        return array_filter($items, [$this, 'itemMeetsCriteria']);
    }

    public function first(array $items): ?object
    {
        foreach ($items as $item) {
            if ($this->itemMeetsCriteria($item)) {
                return $item;
            }
        }

        return null;
    }

    public function match(array $items): bool
    {
        return is_object($this->first($items));
    }

    protected function itemMeetsCriteria(object $item): bool
    {
        return $this->propertyIsMatch($this->resolveValue($item));
    }

    protected function propertyIsMatch($value): bool
    {
        return $this->operationProvider->operate(
            $value,
            $this->operator,
            $this->value
        );
    }
}
