<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\CsvWriterInterface;
use App\Contracts\DirectoryScannerInterface;
use App\Contracts\FileReaderInterface;
use App\Contracts\TagClassifierInterface;
use App\Services\CsvWriter;
use App\Services\LogFileReader;
use App\Services\RecursiveDirectoryScanner;
use App\Services\TagClassifier;
use App\Services\TagStrategies\FreeProductTagStrategy;
use App\Services\TagStrategies\InAppPurchaseProductTagStrategy;
use App\Services\TagStrategies\SubscriptionTagStrategy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DirectoryScannerInterface::class, RecursiveDirectoryScanner::class);
        $this->app->bind(FileReaderInterface::class, LogFileReader::class);

        $this->app->bind(CsvWriterInterface::class, function () {
            /** @var array<int, string> $headers */
            $headers = config('parser.headers');

            return new CsvWriter($headers);
        });

        $this->app->bind(TagClassifierInterface::class, function () {
            return new TagClassifier([
                new SubscriptionTagStrategy(),
                new FreeProductTagStrategy(),
                new InAppPurchaseProductTagStrategy(),
            ]);
        });
    }
}
