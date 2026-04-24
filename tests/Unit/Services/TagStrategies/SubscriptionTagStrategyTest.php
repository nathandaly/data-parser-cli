<?php

use App\Services\TagStrategies\SubscriptionTagStrategy;

beforeEach(function () {
    $this->strategy = new SubscriptionTagStrategy();
});

it('matches all valid subscription tags', function () {
    expect($this->strategy->matches('active_subscriber'))->toBeTrue()
        ->and($this->strategy->matches('expired_subscriber'))->toBeTrue()
        ->and($this->strategy->matches('never_subscribed'))->toBeTrue();
});

it('does not match tags from other groups', function () {
    expect($this->strategy->matches('has_downloaded_free_product'))->toBeFalse()
        ->and($this->strategy->matches('has_downloaded_iap_product'))->toBeFalse();
});

it('does not match unrecognized tags', function () {
    expect($this->strategy->matches('purchased_single_issue_while_active_sub'))->toBeFalse()
        ->and($this->strategy->matches('downloaded_free_single_issue_while_no_sub'))->toBeFalse();
});

it('returns subscription_unknown as the default value', function () {
    expect($this->strategy->defaultValue())->toBe('subscription_unknown');
});
