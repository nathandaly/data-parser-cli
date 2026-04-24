<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\Contactable;
use App\Enums\FreeProductDownloadStatus;
use App\Enums\InAppPurchaseProductDownloadStatus;
use App\Enums\SubscriptionStatus;
use App\Traits\HasArrayable;

readonly class OutputRecord
{
    use HasArrayable;

    public function __construct(
        public int $id,
        public string $appCode,
        public string $deviceId,
        public Contactable $contactable,
        public SubscriptionStatus $subscriptionStatus,
        public FreeProductDownloadStatus $freeProductDownloadStatus,
        public InAppPurchaseProductDownloadStatus $inAppPurchaseProductDownloadStatus,
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function toCsvRow(): array
    {
        return [
            'id' => $this->id,
            'appCode' => $this->appCode,
            'deviceId' => $this->deviceId,
            'contactable' => $this->contactable->value,
            'subscription_status' => $this->subscriptionStatus->value,
            'has_downloaded_free_product_status' => $this->freeProductDownloadStatus->value,
            'has_downloaded_iap_product_status' => $this->inAppPurchaseProductDownloadStatus->value,
        ];
    }
}
