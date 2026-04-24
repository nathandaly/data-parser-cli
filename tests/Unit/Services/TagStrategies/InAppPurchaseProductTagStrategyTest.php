<?php

use App\Services\TagStrategies\InAppPurchaseProductTagStrategy;

beforeEach(function () {
    $this->strategy = new InAppPurchaseProductTagStrategy();
});

it('matches all valid in-app purchase product tags', function () {
    expect($this->strategy->matches('has_downloaded_iap_product'))->toBeTrue()
        ->and($this->strategy->matches('not_downloaded_free_product'))->toBeTrue();
});

it('does not match tags from other groups', function () {
    expect($this->strategy->matches('active_subscriber'))->toBeFalse()
        ->and($this->strategy->matches('has_downloaded_free_product'))->toBeFalse();
});

it('does not match unrecognized tags', function () {
    expect($this->strategy->matches('purchased_single_issue_while_active_sub'))->toBeFalse();
});

it('returns downloaded_iap_product_unknown as the default value', function () {
    expect($this->strategy->defaultValue())->toBe('downloaded_iap_product_unknown');
});
