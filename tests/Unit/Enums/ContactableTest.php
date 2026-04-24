<?php

use App\Enums\Contactable;

it('resolves "1" to Yes (contactable)', function () {
    expect(Contactable::fromDeviceTokenStatus('1'))
        ->toBe(Contactable::Yes);
});

it('resolves empty string to No (not contactable)', function () {
    expect(Contactable::fromDeviceTokenStatus(''))
        ->toBe(Contactable::No);
});

it('resolves "0" to No (not contactable)', function () {
    expect(Contactable::fromDeviceTokenStatus('0'))
        ->toBe(Contactable::No);
});

it('resolves any non-"1" string to No', function () {
    expect(Contactable::fromDeviceTokenStatus('true'))
        ->toBe(Contactable::No)
        ->and(Contactable::fromDeviceTokenStatus('yes'))
        ->toBe(Contactable::No);
});

it('exposes integer backing values 1 and 0', function () {
    expect(Contactable::Yes->value)->toBe(1)
        ->and(Contactable::No->value)->toBe(0);
});
