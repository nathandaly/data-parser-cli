<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\FileReaderInterface;
use App\DTO\RawRecord;
use Generator;
use SplFileInfo;
use SplFileObject;

class LogFileReader implements FileReaderInterface
{
    /**
     * @return Generator<RawRecord>
     */
    public function read(SplFileInfo $file): Generator
    {
        $fileObject = $file->openFile('r');
        $fileObject->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $isHeader = true;

        /** @var array<int, string>|false $row */
        foreach ($fileObject as $row) {
            if ($row === false || $row === [null]) {
                continue;
            }

            if ($isHeader) {
                $isHeader = false;

                continue;
            }

            yield RawRecord::fromCsvRow($row);
        }
    }
}
