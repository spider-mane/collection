<?php

namespace WebTheory\Collection\Query;

use WebTheory\Collection\Contracts\CollectionQueryInterface;
use WebTheory\Collection\Contracts\OperationProviderInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Query\Operation\Operations;
use WebTheory\Collection\Resolution\Abstracts\ResolvesPropertyValueTrait;

class BasicQuery implements CollectionQueryInterface
{
    use ResolvesPropertyValueTrait;

    protected OperationProviderInterface $operationProvider;

    protected string $operator;

    protected $value;

    public function __construct(
        PropertyResolverInterface $propertyResolver,
        string $property,
        string $operator,
        $value,
        OperationProviderInterface $operationProvider = null
    ) {
        $this->propertyResolver = $propertyResolver;
        $this->property = $property;
        $this->operator = $operator;
        $this->value = $value;

        $this->operationProvider = $operationProvider ?? new Operations();
    }

    public function query(array $items): array
    {
        return $this->filter($this->getFiltrationCallback(), $items);
    }

    protected function filter(callable $callback, array $items): array
    {
        return array_values(array_filter($items, $callback));
    }

    protected function getFiltrationCallback(): callable
    {
        return fn ($item) => $this->itemMeetsCriteria($this->resolveValue($item));
    }

    protected function itemMeetsCriteria($value): bool
    {
        return $this->operationProvider->operate($value, $this->operator, $this->value);
    }
}
