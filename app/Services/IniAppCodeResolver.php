<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AppCodeResolverInterface;
use App\Exceptions\IniFileParseException;
use App\Exceptions\UnresolvableAppCodeException;

readonly class IniAppCodeResolver implements AppCodeResolverInterface
{
    /**
     * @var array<string, string>
     */
    private array $reverseMap;

    public function __construct(string $iniFilePath)
    {
        if (!is_file($iniFilePath) || !is_readable($iniFilePath)) {
            throw IniFileParseException::fileNotReadable($iniFilePath);
        }

        $parsed = parse_ini_file($iniFilePath, true, INI_SCANNER_RAW);

        if ($parsed === false) {
            throw IniFileParseException::failedToParse($iniFilePath);
        }

        /** @var array<string, string> $appCodes */
        $appCodes = $parsed['appcodes'] ?? [];

        $reverseMap = [];

        foreach ($appCodes as $kebabKey => $displayName) {
            $displayName = trim($displayName, '"');

            if (!isset($reverseMap[$displayName])) {
                $reverseMap[$displayName] = (string) $kebabKey;
            }
        }

        $this->reverseMap = $reverseMap;
    }

    public function resolve(string $displayName): string
    {
        if (!isset($this->reverseMap[$displayName])) {
            throw UnresolvableAppCodeException::forDisplayName($displayName);
        }

        return $this->reverseMap[$displayName];
    }
}
