<?php

declare(strict_types=1);

namespace App\Commands;

use App\Contracts\CsvWriterInterface;
use App\Contracts\DirectoryScannerInterface;
use App\Contracts\FileReaderInterface;
use App\Contracts\TagClassifierInterface;
use App\Services\IniAppCodeResolver;
use App\Services\RecordTransformer;
use InvalidArgumentException;
use LaravelZero\Framework\Commands\Command;
use Psr\Log\LoggerInterface;
use SplFileInfo;

class ParseCommand extends Command
{
    protected $signature = 'parse
        {source : Root directory containing date subdirectories with .log files}
        {output : Output CSV file path}
        {--ini= : Path to the appCodes.ini file (defaults to PARSER_INI_PATH env / parser.default_ini_path config)}';

    protected $description = 'Parse device token log files and output a classified CSV';

    public function __construct(
        private readonly DirectoryScannerInterface $directoryScanner,
        private readonly FileReaderInterface $fileReader,
        private readonly TagClassifierInterface $tagClassifier,
        private readonly CsvWriterInterface $csvWriter,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $sourcePath = $this->argument('source');
        $outputPath = $this->argument('output');
        $iniOption = $this->option('ini');
        $iniPath = is_string($iniOption) && $iniOption !== '' ? $iniOption : config('parser.default_ini_path');

        if (!is_string($sourcePath) || !is_string($outputPath) || !is_string($iniPath)) {
            throw new InvalidArgumentException('source, output, and --ini must be string values.');
        }

        $appCodeResolver = new IniAppCodeResolver($iniPath);
        $transformer = new RecordTransformer($appCodeResolver, $this->tagClassifier, $this->logger);

        $this->csvWriter->open($outputPath);
        $this->csvWriter->writeHeader();

        $id = 1;

        /** @var SplFileInfo $file */
        foreach ($this->directoryScanner->scan($sourcePath) as $file) {
            foreach ($this->fileReader->read($file) as $rawRecord) {
                $outputRecord = $transformer->transform($rawRecord, $id++);
                $this->csvWriter->writeRecord($outputRecord);
            }
        }

        $this->csvWriter->close();

        $processedCount = $id - 1;

        $this->info("Done. {$processedCount} records written to {$outputPath}.");

        return self::SUCCESS;
    }
}
