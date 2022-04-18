<?php

namespace WebTheory\Collection\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static Order Asc()
 * @method static Order Desc()
 */
final class Order extends Enum
{
    public const Asc = 'asc';
    public const Desc = 'desc';
}
