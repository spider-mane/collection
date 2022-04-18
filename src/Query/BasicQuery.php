<?php

namespace WebTheory\Collection\Query;

use LogicException;
use WebTheory\Collection\Contracts\CollectionQueryInterface;
use WebTheory\Collection\Contracts\PropertyResolverInterface;
use WebTheory\Collection\Resolution\Abstracts\ResolvesPropertyValueTrait;

class BasicQuery implements CollectionQueryInterface
{
    use ResolvesPropertyValueTrait;

    protected string $operator;

    protected $value;

    public function __construct(PropertyResolverInterface $propertyResolver, string $property, string $operator, $value)
    {
        $this->propertyResolver = $propertyResolver;
        $this->property = $property;
        $this->operator = $operator;
        $this->value = $value;
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
        return fn ($item) => $this->itemMeetsCriteria(
            $this->resolveValue($item),
            $this->operator,
            $this->value
        );
    }

    protected function itemMeetsCriteria($value, string $operator, $comparedTo): bool
    {
        switch ($operator) {
            case '=':
                return $value === $comparedTo;

            case '!=':
                return $value !== $comparedTo;

            case '>':
                return $value > $comparedTo;

            case '<':
                return $value < $comparedTo;

            case '>=':
                return $value >= $comparedTo;

            case '<=':
                return $value <= $comparedTo;

            case 'in':
                return in_array($value, $comparedTo);

            case 'not in':
                return !in_array($value, $comparedTo);

            default:
                throw new LogicException(
                    "Querying by operator \"{$operator}\" is not supported."
                );
        }
    }
}
