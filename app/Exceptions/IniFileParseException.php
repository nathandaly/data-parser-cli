<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class IniFileParseException extends RuntimeException
{
    public static function fileNotReadable(string $path): self
    {
        return new self("INI file is not readable or does not exist: {$path}");
    }

    public static function failedToParse(string $path): self
    {
        return new self("Failed to parse INI file: {$path}");
    }
}
