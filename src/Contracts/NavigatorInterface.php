<?php

namespace WebTheory\Collection\Contracts;

interface NavigatorInterface
{
    public function navigate(string $path, $default = null);
}
