<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\OutputRecord;

interface CsvWriterInterface
{
    public function open(string $outputPath): void;

    public function writeHeader(): void;

    public function writeRecord(OutputRecord $record): void;

    public function close(): void;
}
