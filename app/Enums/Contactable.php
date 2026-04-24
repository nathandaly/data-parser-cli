<?php

namespace App\Enums;

enum Contactable: int
{
    case Yes = 1;
    case No = 0;

    public static function fromDeviceTokenStatus(string $value): self
    {
        return match ($value) {
            '1' => self::Yes,
            default => self::No,
        };
    }
}
