<?php

declare(strict_types=1);

beforeEach(function () {
    $this->fixturesPath = __DIR__ . '/../Fixtures';
    $this->sourcePath = $this->fixturesPath . '/scanner';
    $this->iniPath = $this->fixturesPath . '/appCodes.ini';
    $this->outputPath = sys_get_temp_dir() . '/parse-command-test-' . uniqid() . '.csv';
});

afterEach(function () {
    if (file_exists($this->outputPath)) {
        unlink($this->outputPath);
    }
});

it('writes a CSV with the correct headers', function () {
    $this->artisan('parse', [
        'source' => $this->sourcePath,
        'output' => $this->outputPath,
        '--ini' => $this->iniPath,
    ])->assertSuccessful();

    $rows = array_map(fn(string $line) => str_getcsv($line, ',', '"', ''), file($this->outputPath));

    expect($rows[0])->toBe([
        'id',
        'appCode',
        'deviceId',
        'contactable',
        'subscription_status',
        'has_downloaded_free_product_status',
        'has_downloaded_iap_product_status',
    ]);
});

it('processes all records from all log files', function () {
    $this->artisan('parse', [
        'source' => $this->sourcePath,
        'output' => $this->outputPath,
        '--ini' => $this->iniPath,
    ])->assertSuccessful();

    $rows = array_map(fn(string $line) => str_getcsv($line, ',', '"', ''), file($this->outputPath));

    // 1 header + 4 records across 3 log files
    expect($rows)->toHaveCount(5);
});

it('resolves app display names to kebab-case codes', function () {
    $this->artisan('parse', [
        'source' => $this->sourcePath,
        'output' => $this->outputPath,
        '--ini' => $this->iniPath,
    ])->assertSuccessful();

    $rows = array_map(fn(string $line) => str_getcsv($line, ',', '"', ''), file($this->outputPath));

    $appCodes = array_column(array_slice($rows, 1), 1);

    expect($appCodes)->toContain('sfx-collection')
        ->and($appCodes)->toContain('admin-magazine');
});

it('assigns sequential ids starting from 1', function () {
    $this->artisan('parse', [
        'source' => $this->sourcePath,
        'output' => $this->outputPath,
        '--ini' => $this->iniPath,
    ])->assertSuccessful();

    $rows = array_map(fn(string $line) => str_getcsv($line, ',', '"', ''), file($this->outputPath));

    $ids = array_column(array_slice($rows, 1), 0);

    expect($ids)->toBe(['1', '2', '3', '4']);
});

it('maps deviceTokenStatus to the contactable column', function () {
    $this->artisan('parse', [
        'source' => $this->sourcePath,
        'output' => $this->outputPath,
        '--ini' => $this->iniPath,
    ])->assertSuccessful();

    $rows = array_map(fn(string $line) => str_getcsv($line, ',', '"', ''), file($this->outputPath));

    $dataRows = array_slice($rows, 1);
    $contactableValues = array_column($dataRows, 3);

    // The fixtures contain both contactable=1 and contactable=0 records
    expect($contactableValues)->toContain('1')
        ->and($contactableValues)->toContain('0');
});

it('classifies subscription tags correctly', function () {
    $this->artisan('parse', [
        'source' => $this->sourcePath,
        'output' => $this->outputPath,
        '--ini' => $this->iniPath,
    ])->assertSuccessful();

    $rows = array_map(fn(string $line) => str_getcsv($line, ',', '"', ''), file($this->outputPath));

    $dataRows = array_slice($rows, 1);
    $subscriptionStatuses = array_column($dataRows, 4);

    expect($subscriptionStatuses)->toContain('never_subscribed')
        ->and($subscriptionStatuses)->toContain('active_subscriber')
        ->and($subscriptionStatuses)->toContain('subscription_unknown');
});

it('exits with success and prints a summary', function () {
    $this->artisan('parse', [
        'source' => $this->sourcePath,
        'output' => $this->outputPath,
        '--ini' => $this->iniPath,
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('4 records written to');
});
