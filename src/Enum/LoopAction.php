<?php

namespace Enum;

use MyCLabs\Enum\Enum;

/** @method static string Break() */
/** @method static string Continue() */
class LoopAction extends Enum
{
    public const Break = 'break';
    public const Continue = 'continue';
}
