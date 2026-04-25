<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\OutputRecord;
use App\DTO\RawRecord;

interface RecordTransformerInterface
{
    public function transform(RawRecord $record, int $id): OutputRecord;
}
