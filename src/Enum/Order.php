<?php

namespace WebTheory\Collection\Enum;

use MyCLabs\Enum\Enum;
use WebTheory\Collection\Exception\InvalidOrderException;

/**
 * @method static Order Asc()
 * @method static Order Desc()
 */
final class Order extends Enum
{
    public const Asc = 'asc';
    public const Desc = 'desc';

    public static function throwExceptionIfInvalid(string $order): void
    {
        if (!self::isValid($order)) {
            throw new InvalidOrderException($order);
        }
    }
}
