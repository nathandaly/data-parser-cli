<?php

declare(strict_types=1);

namespace App\Contracts;

use SplFileInfo;

interface DirectoryScannerInterface
{
    /**
     * @return iterable<SplFileInfo>
     */
    public function scan(string $basePath): iterable;
}
