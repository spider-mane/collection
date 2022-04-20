<?php

namespace WebTheory\Collection\Iteration;

use WebTheory\Collection\Contracts\LoopInterface;
use WebTheory\Collection\Enum\LoopAction;

class ForeachLoop implements LoopInterface
{
    public function iterate(iterable $items, callable $callback): void
    {
        foreach ($items as $key => $item) {
            $action = $callback($item, $key, $items);

            if ($action instanceof LoopAction) {
                switch ($action->getValue()) {
                    case LoopAction::Break:
                        break 2;

                    case LoopAction::Continue:
                        continue 2;
                }
            }
        }
    }
}
