<?php

declare(strict_types=1);

use App\DTO\OutputRecord;
use App\Enums\Contactable;
use App\Enums\FreeProductDownloadStatus;
use App\Enums\InAppPurchaseProductDownloadStatus;
use App\Enums\SubscriptionStatus;
use App\Services\CsvWriter;

beforeEach(function () {
    $this->headers = [
        'id',
        'appCode',
        'deviceId',
        'contactable',
        'subscription_status',
        'has_downloaded_free_product_status',
        'has_downloaded_iap_product_status',
    ];
    $this->writer = new CsvWriter($this->headers);
    $this->outputPath = sys_get_temp_dir() . '/csv-writer-test-' . uniqid() . '.csv';
});

afterEach(function () {
    if (file_exists($this->outputPath)) {
        unlink($this->outputPath);
    }
});

it('writes the correct header row', function () {
    $this->writer->open($this->outputPath);
    $this->writer->writeHeader();
    $this->writer->close();

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

it('writes a record row with correct values', function () {
    $record = OutputRecord::fromArray([
        'id' => 1,
        'appCode' => 'sfx-collection',
        'deviceId' => 'ABC123',
        'contactable' => Contactable::Yes,
        'subscriptionStatus' => SubscriptionStatus::NeverSubscribed,
        'freeProductDownloadStatus' => FreeProductDownloadStatus::Unknown,
        'inAppPurchaseProductDownloadStatus' => InAppPurchaseProductDownloadStatus::Unknown,
    ]);

    $this->writer->open($this->outputPath);
    $this->writer->writeHeader();
    $this->writer->writeRecord($record);
    $this->writer->close();

    $rows = array_map(fn(string $line) => str_getcsv($line, ',', '"', ''), file($this->outputPath));

    expect($rows[1])->toBe([
        '1',
        'sfx-collection',
        'ABC123',
        '1',
        'never_subscribed',
        'downloaded_free_product_unknown',
        'downloaded_iap_product_unknown',
    ]);
});

it('writes multiple records in order', function () {
    $this->writer->open($this->outputPath);
    $this->writer->writeHeader();

    foreach ([1, 2, 3] as $id) {
        $this->writer->writeRecord(OutputRecord::fromArray([
            'id' => $id,
            'appCode' => 'sfx-collection',
            'deviceId' => "TOKEN{$id}",
            'contactable' => Contactable::No,
            'subscriptionStatus' => SubscriptionStatus::Unknown,
            'freeProductDownloadStatus' => FreeProductDownloadStatus::Unknown,
            'inAppPurchaseProductDownloadStatus' => InAppPurchaseProductDownloadStatus::Unknown,
        ]));
    }

    $this->writer->close();

    $rows = array_map(fn(string $line) => str_getcsv($line, ',', '"', ''), file($this->outputPath));

    expect($rows)->toHaveCount(4)
        ->and($rows[1][0])->toBe('1')
        ->and($rows[2][0])->toBe('2')
        ->and($rows[3][0])->toBe('3');
});

it('throws when writing without opening a file first', function () {
    $this->writer->writeHeader();
})->throws(RuntimeException::class);
