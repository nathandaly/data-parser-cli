<?php

use App\DTO\RawRecord;

it('constructs from named parameters', function () {
    $record = new RawRecord(
        app: 'SFX Collection',
        deviceToken: 'ABC123',
        deviceTokenStatus: '1',
        tags: 'never_subscribed',
    );

    expect($record->app)->toBe('SFX Collection')
        ->and($record->deviceToken)->toBe('ABC123')
        ->and($record->deviceTokenStatus)->toBe('1')
        ->and($record->tags)->toBe('never_subscribed');
});

it('constructs from a CSV row array', function () {
    $record = RawRecord::fromCsvRow([
        'SFX Collection',
        'ABC123',
        '1',
        'active_subscriber|has_downloaded_free_product',
    ]);

    expect($record->app)->toBe('SFX Collection')
        ->and($record->deviceToken)->toBe('ABC123')
        ->and($record->deviceTokenStatus)->toBe('1')
        ->and($record->tags)->toBe('active_subscriber|has_downloaded_free_product');
});

it('handles missing CSV columns gracefully', function () {
    $record = RawRecord::fromCsvRow(['SFX Collection', 'ABC123']);

    expect($record->deviceTokenStatus)->toBe('')
        ->and($record->tags)->toBe('');
});

it('converts to array via HasArrayable trait', function () {
    $record = new RawRecord(
        app: 'ADMIN Magazine',
        deviceToken: 'DEF456',
        deviceTokenStatus: '',
        tags: 'never_subscribed',
    );

    expect($record->toArray())->toBe([
        'app' => 'ADMIN Magazine',
        'deviceToken' => 'DEF456',
        'deviceTokenStatus' => '',
        'tags' => 'never_subscribed',
    ]);
});

it('constructs from array via HasArrayable trait', function () {
    $record = RawRecord::fromArray([
        'app' => 'SFX Collection',
        'deviceToken' => 'ABC123',
        'deviceTokenStatus' => '1',
        'tags' => 'never_subscribed',
    ]);

    expect($record->app)->toBe('SFX Collection')
        ->and($record->deviceToken)->toBe('ABC123');
});
