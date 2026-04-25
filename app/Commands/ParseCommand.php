<?php

declare(strict_types=1);

namespace App\Commands;

use App\Contracts\CsvWriterInterface;
use App\Contracts\DirectoryScannerInterface;
use App\Contracts\FileReaderInterface;
use App\Contracts\TagClassifierInterface;
use App\Services\IniAppCodeResolver;
use App\Services\RecordTransformer;

use function Laravel\Prompts\error;
use function Laravel\Prompts\text;

use LaravelZero\Framework\Commands\Command;
use Psr\Log\LoggerInterface;
use SplFileInfo;

class ParseCommand extends Command
{
    protected $signature = 'parse
        {source? : Root directory containing date subdirectories with .log files}
        {output? : Output CSV file path}
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
        $defaultSourcePath = config('parser.default_source_path');
        $defaultOutputPath = config('parser.default_output_path');

        $sourcePath = $this->argument('source') ?? text(
            label: 'Source directory',
            default: is_string($defaultSourcePath) ? $defaultSourcePath : '',
            required: true,
            validate: fn(string $value) => !is_dir($value)
                ? "The directory \"{$value}\" does not exist."
                : null,
            hint: 'Root directory containing date subdirectories with .log files',
        );

        $outputPath = $this->argument('output') ?? text(
            label: 'Output CSV file',
            default: is_string($defaultOutputPath) ? $defaultOutputPath : '',
            required: true,
            validate: function (string $value) {
                $dir = dirname($value);

                if ($dir !== '.' && !is_dir($dir)) {
                    return "The directory \"{$dir}\" does not exist.";
                }
            },
            hint: 'Path to write the output CSV file',
        );

        $iniOption = $this->option('ini');
        $iniConfig = config('parser.default_ini_path');
        $iniPath = is_string($iniOption) && $iniOption !== ''
            ? $iniOption
            : (is_string($iniConfig) ? $iniConfig : '');

        if (!is_string($sourcePath) || !is_string($outputPath)) {
            error('source and output must be string values.');

            return self::FAILURE;
        }

        if (!is_dir($sourcePath)) {
            error("The source directory \"{$sourcePath}\" does not exist.");

            return self::FAILURE;
        }

        $outputDir = dirname($outputPath);

        if ($outputDir !== '.' && !is_dir($outputDir)) {
            error("The output directory \"{$outputDir}\" does not exist.");

            return self::FAILURE;
        }

        if ($iniPath === '') {
            error('No INI file path could be resolved. Set PARSER_INI_PATH or pass --ini.');

            return self::FAILURE;
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
