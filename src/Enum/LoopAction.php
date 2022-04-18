<?php

namespace WebTheory\Collection\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static LoopAction Break()
 * @method static LoopAction Continue()
 */
final class LoopAction extends Enum
{
    public const Break = 'break';
    public const Continue = 'continue';
}
