<?php

declare(strict_types=1);

use App\DTO\RawRecord;
use App\Services\LogFileReader;

beforeEach(function () {
    $this->reader = new LogFileReader();
});

it('yields RawRecord instances for each data row', function () {
    $file = new SplFileInfo(__DIR__ . '/../../Fixtures/reader/valid.log');
    $records = iterator_to_array($this->reader->read($file), false);

    expect($records)->toHaveCount(3)
        ->and($records[0])->toBeInstanceOf(RawRecord::class);
});

it('skips the header row', function () {
    $file = new SplFileInfo(__DIR__ . '/../../Fixtures/reader/valid.log');
    $records = iterator_to_array($this->reader->read($file), false);

    expect($records[0]->app)->toBe('SFX Collection')
        ->and($records[0]->app)->not->toBe('app');
});

it('correctly maps CSV columns to RawRecord fields', function () {
    $file = new SplFileInfo(__DIR__ . '/../../Fixtures/reader/valid.log');
    $records = iterator_to_array($this->reader->read($file), false);

    expect($records[0]->app)->toBe('SFX Collection')
        ->and($records[0]->deviceToken)->toBe('ABC123DEF456ABC123DEF456ABC123DEF456ABC123DEF456ABC123DEF456ABCD')
        ->and($records[0]->deviceTokenStatus)->toBe('1')
        ->and($records[0]->tags)->toBe('never_subscribed');
});

it('handles empty deviceTokenStatus and tags fields', function () {
    $file = new SplFileInfo(__DIR__ . '/../../Fixtures/reader/valid.log');
    $records = iterator_to_array($this->reader->read($file), false);

    expect($records[2]->deviceTokenStatus)->toBe('')
        ->and($records[2]->tags)->toBe('');
});

it('yields zero records for a header-only file', function () {
    $file = new SplFileInfo(__DIR__ . '/../../Fixtures/reader/header-only.log');
    $records = iterator_to_array($this->reader->read($file), false);

    expect($records)->toBeEmpty();
});

it('preserves raw bracket-wrapped tags without modification', function () {
    $file = new SplFileInfo(__DIR__ . '/../../Fixtures/reader/brackets.log');
    $records = iterator_to_array($this->reader->read($file), false);

    expect($records[0]->tags)->toBe('[never_subscribed]|never_subscribed');
});

it('returns a generator', function () {
    $file = new SplFileInfo(__DIR__ . '/../../Fixtures/reader/valid.log');
    $result = $this->reader->read($file);

    expect($result)->toBeInstanceOf(Generator::class);
});
