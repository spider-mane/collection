<?php

namespace WebTheory\Collection\Contracts;

interface OperationProviderInterface
{
    public function operate($value1, string $operation, $value2): bool;
}
