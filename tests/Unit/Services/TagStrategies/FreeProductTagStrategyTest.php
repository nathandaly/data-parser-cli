<?php

use App\Services\TagStrategies\FreeProductTagStrategy;

beforeEach(function () {
    $this->strategy = new FreeProductTagStrategy();
});

it('matches all valid free product tags', function () {
    expect($this->strategy->matches('has_downloaded_free_product'))->toBeTrue()
        ->and($this->strategy->matches('not_downloaded_free_product'))->toBeTrue();
});

it('does not match tags from other groups', function () {
    expect($this->strategy->matches('active_subscriber'))->toBeFalse()
        ->and($this->strategy->matches('has_downloaded_iap_product'))->toBeFalse();
});

it('does not match unrecognized tags', function () {
    expect($this->strategy->matches('downloaded_free_single_issue_while_no_sub'))->toBeFalse();
});

it('returns downloaded_free_product_unknown as the default value', function () {
    expect($this->strategy->defaultValue())->toBe('downloaded_free_product_unknown');
});
