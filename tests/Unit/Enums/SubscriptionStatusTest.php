<?php

use App\Enums\SubscriptionStatus;

it('resolves active_subscriber tag to ActiveSubscriber case', function () {
    expect(SubscriptionStatus::tryFromTag('active_subscriber'))
        ->toBe(SubscriptionStatus::ActiveSubscriber);
});

it('resolves expired_subscriber tag to ExpiredSubscriber case', function () {
    expect(SubscriptionStatus::tryFromTag('expired_subscriber'))
        ->toBe(SubscriptionStatus::ExpiredSubscriber);
});

it('resolves never_subscribed tag to NeverSubscribed case', function () {
    expect(SubscriptionStatus::tryFromTag('never_subscribed'))
        ->toBe(SubscriptionStatus::NeverSubscribed);
});

it('returns null for an unrecognized tag', function () {
    expect(SubscriptionStatus::tryFromTag('purchased_single_issue_while_active_sub'))
        ->toBeNull();
});

it('does not match tags from other groups', function () {
    expect(SubscriptionStatus::tryFromTag('has_downloaded_free_product'))
        ->toBeNull()
        ->and(SubscriptionStatus::tryFromTag('has_downloaded_iap_product'))
        ->toBeNull();
});

it('has subscription_unknown as the unknown default value', function () {
    expect(SubscriptionStatus::Unknown->value)
        ->toBe('subscription_unknown');
});
