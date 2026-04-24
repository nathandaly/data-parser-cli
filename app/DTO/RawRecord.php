<?php

declare(strict_types=1);

namespace App\DTO;

use App\Traits\HasArrayable;

readonly class RawRecord
{
    use HasArrayable;

    public function __construct(
        public string $app,
        public string $deviceToken,
        public string $deviceTokenStatus,
        public string $tags,
    ) {}

    /**
     * @param array<int, string> $row
     */
    public static function fromCsvRow(array $row): self
    {
        return new self(
            app: $row[0] ?? '',
            deviceToken: $row[1] ?? '',
            deviceTokenStatus: $row[2] ?? '',
            tags: $row[3] ?? '',
        );
    }
}
