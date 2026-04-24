<?php

namespace App\Services\TagStrategies;

use App\Contracts\TagGroupStrategyInterface;
use App\Enums\FreeProductDownloadStatus;

class FreeProductTagStrategy implements TagGroupStrategyInterface
{
    public function matches(string $tag): bool
    {
        return FreeProductDownloadStatus::tryFromTag($tag) !== null;
    }

    public function defaultValue(): string
    {
        return FreeProductDownloadStatus::Unknown->value;
    }
}
