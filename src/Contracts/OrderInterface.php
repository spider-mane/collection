<?php

namespace WebTheory\Collection\Contracts;

interface OrderInterface
{
    public const ASC = 'asc';

    public const DESC = 'desc';

    public static function validate(string $order): bool;

    /**
     * @param string $order
     *
     * @throws InvalidOrderException
     */
    public static function throwExceptionIfInvalid(string $order): void;
}
