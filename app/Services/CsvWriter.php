<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CsvWriterInterface;
use App\DTO\OutputRecord;
use RuntimeException;
use SplFileObject;

class CsvWriter implements CsvWriterInterface
{
    private ?SplFileObject $file = null;

    /**
     * @param array<int, string> $headers
     */
    public function __construct(private readonly array $headers) {}

    public function open(string $outputPath): void
    {
        $this->file = new SplFileObject($outputPath, 'w');
        $this->file->setCsvControl(',', '"', '');
    }

    public function writeHeader(): void
    {
        $this->getFile()->fputcsv($this->headers);
    }

    public function writeRecord(OutputRecord $record): void
    {
        $this->getFile()->fputcsv(array_values($record->toCsvRow()));
    }

    public function close(): void
    {
        $this->file = null;
    }

    private function getFile(): SplFileObject
    {
        if ($this->file === null) {
            throw new RuntimeException('CsvWriter: no file is open. Call open() first.');
        }

        return $this->file;
    }
}
