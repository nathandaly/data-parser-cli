<?php

namespace App\Services\TagStrategies;

use App\Contracts\TagGroupStrategyInterface;
use App\Enums\SubscriptionStatus;

class SubscriptionTagStrategy implements TagGroupStrategyInterface
{
    public function matches(string $tag): bool
    {
        return SubscriptionStatus::tryFromTag($tag) !== null;
    }

    public function defaultValue(): string
    {
        return SubscriptionStatus::Unknown->value;
    }
}
