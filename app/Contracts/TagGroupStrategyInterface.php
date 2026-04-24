<?php

namespace App\Contracts;

interface TagGroupStrategyInterface
{
    public function matches(string $tag): bool;

    public function defaultValue(): string;
}
