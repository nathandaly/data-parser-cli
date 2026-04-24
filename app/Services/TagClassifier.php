<?php

namespace App\Services;

use App\Contracts\TagClassifierInterface;
use App\Contracts\TagGroupStrategyInterface;
use App\DTO\ClassifiedTags;
use App\Enums\FreeProductDownloadStatus;
use App\Enums\InAppPurchaseProductDownloadStatus;
use App\Enums\SubscriptionStatus;
use App\Services\TagStrategies\FreeProductTagStrategy;
use App\Services\TagStrategies\InAppPurchaseProductTagStrategy;
use App\Services\TagStrategies\SubscriptionTagStrategy;

readonly class TagClassifier implements TagClassifierInterface
{
    /**
     * @var array<int, TagGroupStrategyInterface>
     */
    private array $strategies;

    /**
     * @param array<int, TagGroupStrategyInterface> $strategies
     */
    public function __construct(array $strategies)
    {
        $this->strategies = $strategies;
    }

    public function classify(string $tags): ClassifiedTags
    {
        $subscriptionStatus = SubscriptionStatus::Unknown;
        $freeProductDownloadStatus = FreeProductDownloadStatus::Unknown;
        $inAppPurchaseProductDownloadStatus = InAppPurchaseProductDownloadStatus::Unknown;
        $unrecognizedTags = [];

        if (trim($tags) === '') {
            return new ClassifiedTags(
                $subscriptionStatus,
                $freeProductDownloadStatus,
                $inAppPurchaseProductDownloadStatus,
            );
        }

        /** @var array<int, bool> $matchedStrategies */
        $matchedStrategies = [];

        foreach (explode('|', $tags) as $rawTag) {
            $tag = $this->sanitizeTag($rawTag);

            if ($tag === '') {
                continue;
            }

            $matched = false;

            foreach ($this->strategies as $index => $strategy) {
                if (!$strategy->matches($tag)) {
                    continue;
                }

                $matched = true;

                if (isset($matchedStrategies[$index])) {
                    break;
                }

                $matchedStrategies[$index] = true;

                match (true) {
                    $strategy instanceof SubscriptionTagStrategy => $subscriptionStatus = SubscriptionStatus::tryFromTag($tag) ?? SubscriptionStatus::Unknown,
                    $strategy instanceof FreeProductTagStrategy => $freeProductDownloadStatus = FreeProductDownloadStatus::tryFromTag($tag) ?? FreeProductDownloadStatus::Unknown,
                    $strategy instanceof InAppPurchaseProductTagStrategy => $inAppPurchaseProductDownloadStatus = InAppPurchaseProductDownloadStatus::tryFromTag($tag) ?? InAppPurchaseProductDownloadStatus::Unknown,
                    default => null,
                };

                break;
            }

            if (!$matched) {
                $unrecognizedTags[] = $tag;
            }
        }

        return new ClassifiedTags(
            $subscriptionStatus,
            $freeProductDownloadStatus,
            $inAppPurchaseProductDownloadStatus,
            $unrecognizedTags,
        );
    }

    private function sanitizeTag(string $tag): string
    {
        return trim($tag, " \t\n\r\0\x0B[]");
    }
}
