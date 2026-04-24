<?php

use App\DTO\OutputRecord;
use App\Enums\Contactable;
use App\Enums\FreeProductDownloadStatus;
use App\Enums\InAppPurchaseProductDownloadStatus;
use App\Enums\SubscriptionStatus;

it('constructs from array via HasArrayable trait', function () {
    $record = OutputRecord::fromArray([
        'id' => 1,
        'appCode' => 'sfx-collection',
        'deviceId' => 'ABC123',
        'contactable' => Contactable::Yes,
        'subscriptionStatus' => SubscriptionStatus::ActiveSubscriber,
        'freeProductDownloadStatus' => FreeProductDownloadStatus::HasDownloaded,
        'inAppPurchaseProductDownloadStatus' => InAppPurchaseProductDownloadStatus::Unknown,
    ]);

    expect($record->id)->toBe(1)
        ->and($record->appCode)->toBe('sfx-collection')
        ->and($record->deviceId)->toBe('ABC123')
        ->and($record->contactable)->toBe(Contactable::Yes);
});

it('produces a CSV-ready row with enum values resolved', function () {
    $record = OutputRecord::fromArray([
        'id' => 42,
        'appCode' => 'admin-magazine',
        'deviceId' => 'DEF456',
        'contactable' => Contactable::No,
        'subscriptionStatus' => SubscriptionStatus::NeverSubscribed,
        'freeProductDownloadStatus' => FreeProductDownloadStatus::NotDownloaded,
        'inAppPurchaseProductDownloadStatus' => InAppPurchaseProductDownloadStatus::HasDownloaded,
    ]);

    expect($record->toCsvRow())->toBe([
        'id' => 42,
        'appCode' => 'admin-magazine',
        'deviceId' => 'DEF456',
        'contactable' => 0,
        'subscription_status' => 'never_subscribed',
        'has_downloaded_free_product_status' => 'not_downloaded_free_product',
        'has_downloaded_iap_product_status' => 'has_downloaded_iap_product',
    ]);
});

it('uses unknown defaults correctly in CSV output', function () {
    $record = OutputRecord::fromArray([
        'id' => 1,
        'appCode' => 'sfx-collection',
        'deviceId' => 'ABC123',
        'contactable' => Contactable::Yes,
        'subscriptionStatus' => SubscriptionStatus::Unknown,
        'freeProductDownloadStatus' => FreeProductDownloadStatus::Unknown,
        'inAppPurchaseProductDownloadStatus' => InAppPurchaseProductDownloadStatus::Unknown,
    ]);

    $row = $record->toCsvRow();

    expect($row['subscription_status'])->toBe('subscription_unknown')
        ->and($row['has_downloaded_free_product_status'])->toBe('downloaded_free_product_unknown')
        ->and($row['has_downloaded_iap_product_status'])->toBe('downloaded_iap_product_unknown');
});

it('converts to array via HasArrayable trait preserving enum instances', function () {
    $record = OutputRecord::fromArray([
        'id' => 1,
        'appCode' => 'sfx-collection',
        'deviceId' => 'ABC123',
        'contactable' => Contactable::Yes,
        'subscriptionStatus' => SubscriptionStatus::ActiveSubscriber,
        'freeProductDownloadStatus' => FreeProductDownloadStatus::HasDownloaded,
        'inAppPurchaseProductDownloadStatus' => InAppPurchaseProductDownloadStatus::Unknown,
    ]);

    $array = $record->toArray();

    expect($array['id'])->toBe(1)
        ->and($array['contactable'])->toBe(Contactable::Yes)
        ->and($array['subscriptionStatus'])->toBe(SubscriptionStatus::ActiveSubscriber);
});
