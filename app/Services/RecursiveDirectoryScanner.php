<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DirectoryScannerInterface;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class RecursiveDirectoryScanner implements DirectoryScannerInterface
{
    /**
     * @return Generator<SplFileInfo>
     */
    public function scan(string $basePath): Generator
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'log') {
                yield $file;
            }
        }
    }
}
