<?php

namespace Enum;

use MyCLabs\Enum\Enum;

/** @method static string Asc() */
/** @method static string Desc() */
class Order extends Enum
{
    public const Asc = 'asc';
    public const Desc = 'desc';
}
