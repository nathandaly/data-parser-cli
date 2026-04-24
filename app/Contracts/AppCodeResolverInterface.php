<?php

declare(strict_types=1);

namespace App\Contracts;

interface AppCodeResolverInterface
{
    public function resolve(string $displayName): string;
}
