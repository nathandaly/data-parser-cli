<?php

use App\Enums\FreeProductDownloadStatus;

it('resolves has_downloaded_free_product tag to HasDownloaded case', function () {
    expect(FreeProductDownloadStatus::tryFromTag('has_downloaded_free_product'))
        ->toBe(FreeProductDownloadStatus::HasDownloaded);
});

it('resolves not_downloaded_free_product tag to NotDownloaded case', function () {
    expect(FreeProductDownloadStatus::tryFromTag('not_downloaded_free_product'))
        ->toBe(FreeProductDownloadStatus::NotDownloaded);
});

it('returns null for an unrecognized tag', function () {
    expect(FreeProductDownloadStatus::tryFromTag('downloaded_free_single_issue_while_no_sub'))
        ->toBeNull();
});

it('does not match tags from other groups', function () {
    expect(FreeProductDownloadStatus::tryFromTag('active_subscriber'))
        ->toBeNull()
        ->and(FreeProductDownloadStatus::tryFromTag('has_downloaded_iap_product'))
        ->toBeNull();
});

it('has downloaded_free_product_unknown as the unknown default value', function () {
    expect(FreeProductDownloadStatus::Unknown->value)
        ->toBe('downloaded_free_product_unknown');
});
