<?php

declare(strict_types=1);

use App\Services\RecursiveDirectoryScanner;

beforeEach(function () {
    $this->scanner = new RecursiveDirectoryScanner();
    $this->fixturesPath = __DIR__ . '/../../Fixtures/scanner';
});

it('yields only .log files from nested directories', function () {
    $files = iterator_to_array($this->scanner->scan($this->fixturesPath), false);

    $extensions = array_map(fn(SplFileInfo $f) => $f->getExtension(), $files);

    expect($extensions)->each->toBe('log');
});

it('finds log files across multiple subdirectories', function () {
    $files = iterator_to_array($this->scanner->scan($this->fixturesPath), false);

    expect($files)->toHaveCount(3);
});

it('ignores non-.log files', function () {
    $files = iterator_to_array($this->scanner->scan($this->fixturesPath), false);

    $names = array_map(fn(SplFileInfo $f) => $f->getFilename(), $files);

    expect($names)->not->toContain('not-a-log.txt');
});

it('yields SplFileInfo instances', function () {
    $files = iterator_to_array($this->scanner->scan($this->fixturesPath), false);

    expect($files[0])->toBeInstanceOf(SplFileInfo::class);
});

it('returns a generator', function () {
    $result = $this->scanner->scan($this->fixturesPath);

    expect($result)->toBeInstanceOf(Generator::class);
});
