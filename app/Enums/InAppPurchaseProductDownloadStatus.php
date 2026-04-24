<?php

namespace App\Enums;

enum InAppPurchaseProductDownloadStatus: string
{
    case HasDownloaded = 'has_downloaded_iap_product';

    case NotDownloaded = 'not_downloaded_free_product';

    case Unknown = 'downloaded_iap_product_unknown';

    public static function tryFromTag(string $tag): ?self
    {
        return match ($tag) {
            'has_downloaded_iap_product' => self::HasDownloaded,
            'not_downloaded_free_product' => self::NotDownloaded,
            default => null,
        };
    }
}
