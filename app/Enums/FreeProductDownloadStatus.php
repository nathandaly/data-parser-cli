<?php

namespace App\Enums;

enum FreeProductDownloadStatus: string
{
    case HasDownloaded = 'has_downloaded_free_product';

    case NotDownloaded = 'not_downloaded_free_product';

    case Unknown = 'downloaded_free_product_unknown';

    public static function tryFromTag(string $tag): ?self
    {
        return match ($tag) {
            'has_downloaded_free_product' => self::HasDownloaded,
            'not_downloaded_free_product' => self::NotDownloaded,
            default => null,
        };
    }
}
