<?php

use App\DTO\ClassifiedTags;
use App\Enums\FreeProductDownloadStatus;
use App\Enums\InAppPurchaseProductDownloadStatus;
use App\Enums\SubscriptionStatus;
use App\Services\TagClassifier;
use App\Services\TagStrategies\FreeProductTagStrategy;
use App\Services\TagStrategies\InAppPurchaseProductTagStrategy;
use App\Services\TagStrategies\SubscriptionTagStrategy;

beforeEach(function () {
    $this->classifier = new TagClassifier([
        new SubscriptionTagStrategy(),
        new FreeProductTagStrategy(),
        new InAppPurchaseProductTagStrategy(),
    ]);
});

it('returns all unknown defaults for empty tags', function () {
    $result = $this->classifier->classify('');

    expect($result->subscriptionStatus)->toBe(SubscriptionStatus::Unknown)
        ->and($result->freeProductDownloadStatus)->toBe(FreeProductDownloadStatus::Unknown)
        ->and($result->inAppPurchaseProductDownloadStatus)->toBe(InAppPurchaseProductDownloadStatus::Unknown)
        ->and($result->unrecognizedTags)->toBeEmpty();
});

it('classifies a single subscription tag', function () {
    $result = $this->classifier->classify('never_subscribed');

    expect($result->subscriptionStatus)->toBe(SubscriptionStatus::NeverSubscribed)
        ->and($result->freeProductDownloadStatus)->toBe(FreeProductDownloadStatus::Unknown)
        ->and($result->inAppPurchaseProductDownloadStatus)->toBe(InAppPurchaseProductDownloadStatus::Unknown);
});

it('classifies multiple tags across different groups', function () {
    $result = $this->classifier->classify('active_subscriber|has_downloaded_free_product|has_downloaded_iap_product');

    expect($result->subscriptionStatus)->toBe(SubscriptionStatus::ActiveSubscriber)
        ->and($result->freeProductDownloadStatus)->toBe(FreeProductDownloadStatus::HasDownloaded)
        ->and($result->inAppPurchaseProductDownloadStatus)->toBe(InAppPurchaseProductDownloadStatus::HasDownloaded);
});

it('collects unrecognized tags without dropping the record', function () {
    $result = $this->classifier->classify('purchased_single_issue_while_active_sub|active_subscriber|downloaded_free_single_issue_while_active_sub');

    expect($result->subscriptionStatus)->toBe(SubscriptionStatus::ActiveSubscriber)
        ->and($result->unrecognizedTags)->toBe([
            'purchased_single_issue_while_active_sub',
            'downloaded_free_single_issue_while_active_sub',
        ]);
});

it('strips bracket characters from tags', function () {
    $result = $this->classifier->classify('[never_subscribed]|never_subscribed');

    expect($result->subscriptionStatus)->toBe(SubscriptionStatus::NeverSubscribed)
        ->and($result->unrecognizedTags)->toBeEmpty();
});

it('handles mismatched brackets in tags', function () {
    $result = $this->classifier->classify('never_subscribed]|[downloaded_free_single_issue_while_no_sub');

    expect($result->subscriptionStatus)->toBe(SubscriptionStatus::NeverSubscribed)
        ->and($result->unrecognizedTags)->toBe(['downloaded_free_single_issue_while_no_sub']);
});

it('uses first match when multiple tags belong to the same group', function () {
    $result = $this->classifier->classify('active_subscriber|expired_subscriber');

    expect($result->subscriptionStatus)->toBe(SubscriptionStatus::ActiveSubscriber);
});

it('handles real-world data with mixed valid and unrecognized tags', function () {
    $result = $this->classifier->classify('downloaded_free_single_issue_while_no_sub|never_subscribed');

    expect($result->subscriptionStatus)->toBe(SubscriptionStatus::NeverSubscribed)
        ->and($result->unrecognizedTags)->toBe(['downloaded_free_single_issue_while_no_sub']);
});

it('returns a ClassifiedTags instance', function () {
    $result = $this->classifier->classify('');

    expect($result)->toBeInstanceOf(ClassifiedTags::class);
});
