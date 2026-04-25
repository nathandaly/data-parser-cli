<?php

declare(strict_types=1);

namespace App\Traits;

use function Laravel\Prompts\error;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

trait InteractsWithFiles
{
    private function isValidSource(string $path): bool
    {
        return is_dir($path) || (is_file($path) && str_ends_with($path, '.zip'));
    }

    private function extractZip(string $zipPath, string $targetDirectory): bool
    {
        if (is_dir($targetDirectory)) {
            $this->deleteDirectory($targetDirectory);
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($zipPath);

        if ($openResult !== true) {
            error("Failed to open \"{$zipPath}\" (ZipArchive error code: {$openResult}).");

            return false;
        }

        if (!$zip->extractTo($targetDirectory)) {
            $zip->close();
            error("Failed to extract \"{$zipPath}\" to \"{$targetDirectory}\".");

            return false;
        }

        $zip->close();

        return true;
    }

    private function deleteDirectory(string $path): void
    {
        /** @var iterable<SplFileInfo> $files */
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $realPath = $file->getRealPath();

            if ($realPath === false) {
                continue;
            }

            if ($file->isDir()) {
                rmdir($realPath);
            } else {
                unlink($realPath);
            }
        }

        rmdir($path);
    }
}
