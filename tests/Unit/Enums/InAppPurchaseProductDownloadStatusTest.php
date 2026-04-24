<?php

use App\Enums\InAppPurchaseProductDownloadStatus;

it('resolves has_downloaded_iap_product tag to HasDownloaded case', function () {
    expect(InAppPurchaseProductDownloadStatus::tryFromTag('has_downloaded_iap_product'))
        ->toBe(InAppPurchaseProductDownloadStatus::HasDownloaded);
});

it('resolves not_downloaded_free_product tag to NotDownloaded case', function () {
    expect(InAppPurchaseProductDownloadStatus::tryFromTag('not_downloaded_free_product'))
        ->toBe(InAppPurchaseProductDownloadStatus::NotDownloaded);
});

it('returns null for an unrecognized tag', function () {
    expect(InAppPurchaseProductDownloadStatus::tryFromTag('purchased_single_issue_while_active_sub'))
        ->toBeNull();
});

it('does not match tags from other groups', function () {
    expect(InAppPurchaseProductDownloadStatus::tryFromTag('active_subscriber'))
        ->toBeNull()
        ->and(InAppPurchaseProductDownloadStatus::tryFromTag('has_downloaded_free_product'))
        ->toBeNull();
});

it('has downloaded_iap_product_unknown as the unknown default value', function () {
    expect(InAppPurchaseProductDownloadStatus::Unknown->value)
        ->toBe('downloaded_iap_product_unknown');
});
