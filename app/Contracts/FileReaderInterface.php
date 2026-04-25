<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\RawRecord;
use SplFileInfo;

interface FileReaderInterface
{
    /**
     * @return iterable<RawRecord>
     */
    public function read(SplFileInfo $file): iterable;
}
