<?php

namespace App\DTO;

use App\Enums\FreeProductDownloadStatus;
use App\Enums\InAppPurchaseProductDownloadStatus;
use App\Enums\SubscriptionStatus;

readonly class ClassifiedTags
{
    /**
     * @param array<int, string> $unrecognizedTags
     */
    public function __construct(
        public SubscriptionStatus $subscriptionStatus,
        public FreeProductDownloadStatus $freeProductDownloadStatus,
        public InAppPurchaseProductDownloadStatus $inAppPurchaseProductDownloadStatus,
        public array $unrecognizedTags = [],
    ) {}
}
