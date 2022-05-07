<?php

namespace WebTheory\Collection\Exception;

use InvalidArgumentException;
use WebTheory\Collection\Contracts\InvalidOrderExceptionInterface;
use WebTheory\Collection\Enum\Order;

class InvalidOrderException extends InvalidArgumentException implements InvalidOrderExceptionInterface
{
    public function __construct(string $given)
    {
        parent::__construct(
            sprintf(
                'Order must be either "%s" or "%s", "%s" given',
                Order::Asc,
                Order::Desc,
                $given
            )
        );
    }
}
