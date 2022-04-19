<?php

namespace WebTheory\Collection\Query\Operation;

use LogicException;
use WebTheory\Collection\Contracts\OperationProviderInterface;

class Operations implements OperationProviderInterface
{
    protected array $operators;

    public function __construct(array $operators = [])
    {
        $this->operators = $operators + $this->defaultOperators();
    }

    public function operate($value1, string $operator, $value2): bool
    {
        $operator = $this->getOperator($operator);

        return $operator($value1, $value2);
    }

    protected function getOperator(string $operator): callable
    {
        if ($resolved = $this->operators[$operator] ?? false) {
            return $resolved;
        }

        throw new LogicException(
            "Querying by operator \"{$operator}\" is not supported."
        );
    }

    protected function defaultOperators(): array
    {
        return [
            '=' => fn ($a, $b): bool => $a === $b,

            '!=' => fn ($a, $b): bool => $a !== $b,

            '>' => fn ($a, $b): bool => $a > $b,

            '<' => fn ($a, $b): bool => $a < $b,

            '>=' => fn ($a, $b): bool => $a >= $b,

            '<=' => fn ($a, $b): bool => $a <= $b,

            'in' => fn ($a, $b): bool => in_array($a, $b),

            'not in' => fn ($a, $b): bool => !in_array($a, $b),

            'like' => fn ($a, $b): bool => $a == $b,

            'not like' => fn ($a, $b): bool => $a != $b,

            'between' => fn ($a, $b): bool => $a >= $b[0] && $a <= $b[1],

            'not between' => fn ($a, $b): bool => $a < $b[0] || $a > $b[1],
        ];
    }
}
