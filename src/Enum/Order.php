<?php

namespace WebTheory\Collection\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static Order Asc()
 * @method static Order Desc()
 */
final class Order extends Enum
{
    private const Asc = 'asc';
    private const Desc = 'desc';
}
