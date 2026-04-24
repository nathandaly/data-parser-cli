<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class UnresolvableAppCodeException extends RuntimeException
{
    public static function forDisplayName(string $displayName): self
    {
        return new self("Unknown app display name: {$displayName}");
    }
}
