<?php

namespace App\Services\TagStrategies;

use App\Contracts\TagGroupStrategyInterface;
use App\Enums\InAppPurchaseProductDownloadStatus;

class InAppPurchaseProductTagStrategy implements TagGroupStrategyInterface
{
    public function matches(string $tag): bool
    {
        return InAppPurchaseProductDownloadStatus::tryFromTag($tag) !== null;
    }

    public function defaultValue(): string
    {
        return InAppPurchaseProductDownloadStatus::Unknown->value;
    }
}
