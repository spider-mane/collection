<?php

namespace WebTheory\Collection\Sorting;

use WebTheory\Collection\Contracts\OrderInterface;
use WebTheory\Collection\Exception\InvalidOrderException;

class Order implements OrderInterface
{
    public static function validate(string $order): bool
    {
        return in_array($order, [self::ASC, self::DESC]);
    }

    public static function throwExceptionIfInvalid(string $order): void
    {
        if (!self::validate($order)) {
            throw new InvalidOrderException($order);
        }
    }
}
