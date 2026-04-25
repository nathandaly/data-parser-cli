<?php

declare(strict_types=1);

namespace App\Commands;

use App\Contracts\CsvWriterInterface;
use App\Contracts\DirectoryScannerInterface;
use App\Contracts\FileReaderInterface;
use App\Contracts\TagClassifierInterface;
use App\Services\IniAppCodeResolver;
use App\Services\RecordTransformer;
use App\Traits\InteractsWithFiles;

use function Laravel\Prompts\error;
use function Laravel\Prompts\text;

use LaravelZero\Framework\Commands\Command;
use Psr\Log\LoggerInterface;
use SplFileInfo;

class ParseCommand extends Command
{
    use InteractsWithFiles;

    private const string TEMPORARY_DIRECTORY = './tmp';

    protected $signature = 'parse
        {source? : Root directory or .zip file containing date subdirectories with .log files}
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
            label: 'Source directory or .zip file',
            default: is_string($defaultSourcePath) ? $defaultSourcePath : '',
            required: true,
            validate: fn(string $value) => !$this->isValidSource($value)
                ? "The path \"{$value}\" must be an existing directory or a .zip file."
                : null,
            hint: 'Root directory or .zip file containing date subdirectories with .log files',
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

        if (!$this->isValidSource($sourcePath)) {
            error("The source \"{$sourcePath}\" must be an existing directory or a .zip file.");

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

        $temporaryDirectory = null;

        if (str_ends_with($sourcePath, '.zip')) {
            if (!$this->extractZip($sourcePath, self::TEMPORARY_DIRECTORY)) {
                return self::FAILURE;
            }

            $temporaryDirectory = self::TEMPORARY_DIRECTORY;
            $sourcePath = self::TEMPORARY_DIRECTORY;

            $extractedIniPath = $temporaryDirectory . DIRECTORY_SEPARATOR . $iniPath;

            if (is_file($extractedIniPath)) {
                $iniPath = $extractedIniPath;
            }
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

        if ($temporaryDirectory !== null) {
            $this->deleteDirectory($temporaryDirectory);
        }

        $processedCount = $id - 1;

        $this->info("Done. {$processedCount} records written to {$outputPath}.");

        return self::SUCCESS;
    }
}
