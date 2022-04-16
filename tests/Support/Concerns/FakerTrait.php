<?php

declare(strict_types=1);

namespace Tests\Support\Concerns;

use Faker\Factory;
use Faker\Generator;
use Faker\UniqueGenerator;

trait FakerTrait
{
    protected Generator $fake;

    protected UniqueGenerator $unique;

    protected function initFaker(): void
    {
        $this->fake = $this->createFaker();
        $this->unique = $this->fake->unique();
    }

    protected function createFaker(): Generator
    {
        return Factory::create();
    }

    protected function dummyList(callable $generator, int $count = 10): array
    {
        return array_map(fn () => $generator(), range(1, $count));
    }

    protected function dummyMap(callable $generator, array $keys): array
    {
        return array_map(fn () => $generator(), array_flip($keys));
    }

    protected function dummyKeyMap(callable $keyGen, callable $valueGen, int $count = 10): array
    {
        return $this->dummyMap($valueGen, $this->dummyList($keyGen, $count));
    }
}
