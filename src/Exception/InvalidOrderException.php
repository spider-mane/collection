<?php

namespace WebTheory\Collection\Exception;

use InvalidArgumentException;
use WebTheory\Collection\Contracts\InvalidOrderExceptionInterface;
use WebTheory\Collection\Contracts\OrderInterface;

class InvalidOrderException extends InvalidArgumentException implements InvalidOrderExceptionInterface
{
    public function __construct(string $given)
    {
        parent::__construct(
            sprintf(
                'Order must be either "%s" or "%s", "%s" given',
                OrderInterface::ASC,
                OrderInterface::DESC,
                $given
            )
        );
    }
}
