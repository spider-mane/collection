<?php

namespace WebTheory\Collection\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static LoopAction Break()
 * @method static LoopAction Continue()
 */
final class LoopAction extends Enum
{
    private const Break = 'break';
    private const Continue = 'continue';
}
