<?php

use App\Exceptions\IniFileParseException;
use App\Exceptions\UnresolvableAppCodeException;
use App\Services\IniAppCodeResolver;

beforeEach(function () {
    $this->resolver = new IniAppCodeResolver(
        __DIR__ . '/../../Fixtures/appCodes.ini',
    );
});

it('resolves a known display name to its kebab-case key', function () {
    expect($this->resolver->resolve('SFX Collection'))->toBe('sfx-collection')
        ->and($this->resolver->resolve('ADMIN Magazine'))->toBe('admin-magazine')
        ->and($this->resolver->resolve('ImagineFX'))->toBe('imaginefx');
});

it('resolves display names with special characters', function () {
    expect($this->resolver->resolve('MAC|LIFE'))->toBe('maclife')
        ->and($this->resolver->resolve('.net'))->toBe('net')
        ->and($this->resolver->resolve('Autotrader: Ignition'))->toBe('autotrader-ignition');
});

it('uses the first key when duplicate display names exist', function () {
    expect($this->resolver->resolve('GUITARIST DELUXE PUSH SERVICE'))
        ->toBe('guitarist-deluxe-push-service');
});

it('throws UnresolvableAppCodeException for an unknown display name', function () {
    $this->resolver->resolve('Nonexistent App');
})->throws(UnresolvableAppCodeException::class, 'Unknown app display name: Nonexistent App');

it('throws IniFileParseException for an invalid INI file path', function () {
    new IniAppCodeResolver('/nonexistent/path/appCodes.ini');
})->throws(IniFileParseException::class, 'INI file is not readable or does not exist');
