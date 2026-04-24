<?php

declare(strict_types=1);

namespace App\Traits;

use ReflectionClass;

trait HasArrayable
{
    /**
     * 🔆 I wanted to keep php 8.4 constructor promotion and allow fromArray().
     *
     * @param array<string, mixed> $attributes
     */
    public static function fromArray(array $attributes): static
    {
        $params = [];
        $constructor = new ReflectionClass(static::class)->getConstructor();

        foreach ($constructor?->getParameters() ?? [] as $param) {
            $paramName = $param->getName();

            if (!array_key_exists($paramName, $attributes)) {
                continue;
            }

            $params[$paramName] = $attributes[$paramName];
        }

        /** @phpstan-ignore-next-line */
        return new static(...$params);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        /** @var array<string, mixed> $attributes */
        $attributes = get_object_vars($this);

        return $attributes;
    }
}
