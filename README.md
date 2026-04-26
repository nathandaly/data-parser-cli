# Parser CLI

[![PHP Package Checks](https://github.com/nathandaly/data-parser-cli/actions/workflows/php.yml/badge.svg)](https://github.com/nathandaly/data-parser-cli/actions/workflows/php.yml)

A Laravel Zero CLI tool for processing device token log files exported from a third-party push notification service. It reads ~73,000 records across 74 `.log` files, classifies pipe-delimited tags into three output groups, resolves app display names to kebab-case codes via an INI reverse map, and writes a clean CSV ready for import.

The tool accepts a source directory or a `.zip` archive, interacts with the user through [Laravel Prompts](https://laravel.com/docs/13.x/prompts) when arguments are omitted, and streams records through PHP generators to keep memory use flat regardless of input size.

## Requirements

- PHP 8.4+
- Composer
- The `zip` PHP extension (for `.zip` source support)
- PSR-3 logger support through `psr/log`

## Quick Start

```bash
# Run interactively — prompts for source and output paths
php parser-cli parse

# Pass arguments directly
php parser-cli parse parser_test output.csv

# With a zip archive
php parser-cli parse parser_test.zip output.csv

# Override the default INI path
php parser-cli parse parser_test output.csv --ini=path/to/appCodes.ini
```

## Architecture

The tool is built around a single-pass pipeline that connects small, focused components through interfaces:

```
ParseCommand
  └─ RecursiveDirectoryScanner   finds all .log files
  └─ LogFileReader               streams RawRecord DTOs from each file
  └─ RecordTransformer           orchestrates per-record transformation
       ├─ IniAppCodeResolver     resolves display name → kebab-case code
       └─ TagClassifier          classifies pipe-delimited tags
            ├─ SubscriptionTagStrategy
            ├─ FreeProductTagStrategy
            └─ InAppPurchaseProductTagStrategy
  └─ CsvWriter                   writes OutputRecord DTOs to CSV
```

Each layer depends only on its interface contract. The `AppServiceProvider` binds the concrete implementations. `ParseCommand` creates `IniAppCodeResolver` at runtime with the resolved INI path (since the path is a runtime argument, not a container-time constant).

## Design Patterns

### Strategy Pattern — tag classification

Tag classification is the most complex part of the pipeline. Each input record carries a pipe-delimited string of tags such as `active_subscriber|has_downloaded_free_product`, and each tag belongs to exactly one of three output columns.

A `TagGroupStrategyInterface` defines two methods:

```php
interface TagGroupStrategyInterface
{
    public function matches(string $tag): bool;
    public function defaultValue(): string;
}
```

Three concrete strategies implement it — `SubscriptionTagStrategy`, `FreeProductTagStrategy`, and `InAppPurchaseProductTagStrategy`. Each delegates `matches()` to its own enum's `tryFromTag()` named constructor.

`TagClassifier` holds an ordered array of strategies. For each sanitised tag, it walks the strategies to find the first match, assigns the result to the appropriate group, and collects any unmatched tags as unrecognised. First-match-wins per group — if two tags from the same group appear in one record, the second is silently ignored rather than overwriting the first.

```php
match (true) {
    $strategy instanceof SubscriptionTagStrategy         => $subscriptionStatus = ...,
    $strategy instanceof FreeProductTagStrategy          => $freeProductDownloadStatus = ...,
    $strategy instanceof InAppPurchaseProductTagStrategy => $inAppPurchaseProductDownloadStatus = ...,
    default => null,
};
```

Adding a new tag group is a single-file change: implement the interface, add an enum, and register the strategy in the service provider.

### PHP 8.4 backed enums with named constructors

All finite value sets are backed enums. Each enum exposes a `tryFromTag(string): ?self` named constructor that returns `null` for unrecognised input rather than throwing. The strategy pattern builds on top of this: `matches()` is simply `tryFromTag($tag) !== null`.

```php
enum SubscriptionStatus: string
{
    case ActiveSubscriber = 'active_subscriber';
    case ExpiredSubscriber = 'expired_subscriber';
    case NeverSubscribed   = 'never_subscribed';
    case Unknown           = 'subscription_unknown';

    public static function tryFromTag(string $tag): ?self
    {
        return match ($tag) {
            'active_subscriber'  => self::ActiveSubscriber,
            'expired_subscriber' => self::ExpiredSubscriber,
            'never_subscribed'   => self::NeverSubscribed,
            default              => null,
        };
    }
}
```

`Contactable` is an int-backed enum (`Yes = 1`, `No = 0`) with a `fromDeviceTokenStatus(string): self` named constructor — only `'1'` maps to `Yes`, everything else maps to `No`.

### Immutable readonly DTOs with `HasArrayable`

All data objects are PHP 8.2 `readonly class` instances. A `HasArrayable` trait provides `fromArray(array): static` via reflection over constructor parameters, and `toArray(): array` via `get_object_vars()`. This keeps constructor promotion and named arguments as the canonical hydration path while still supporting array-based construction in tests and command wiring.

```php
readonly class RawRecord
{
    use HasArrayable;

    public function __construct(
        public string $app,
        public string $deviceToken,
        public string $deviceTokenStatus,
        public string $tags,
    ) {}
}
```

`OutputRecord::toCsvRow()` resolves enum values to their primitive backing values for CSV serialisation without mutating the object.

### Generator-based I/O

Both `RecursiveDirectoryScanner::scan()` and `LogFileReader::read()` use PHP generators. 73,000 records stream through memory one at a time — peak memory is driven by a single record, not the full dataset.

`LogFileReader` wraps `SplFileObject` with `READ_CSV | SKIP_EMPTY | DROP_NEW_LINE` flags and yields `RawRecord::fromCsvRow($row)` for each data row after skipping the header.

For the progress bar, files are collected upfront (`iterator_to_array`) to obtain a count for the steps parameter. The file list is small (74 `SplFileInfo` objects), so this does not affect the memory profile for the actual data.

### Reverse INI mapping

`IniAppCodeResolver` parses `appCodes.ini` with `parse_ini_file(..., INI_SCANNER_RAW)` and builds a reverse map from display name to kebab-case key. The INI has duplicate display names for some entries; first-occurrence-wins is applied at build time. The resolver throws named exceptions for every failure path:

- `IniFileParseException::fileNotReadable(string $path)` — file not found or unreadable.
- `IniFileParseException::failedToParse(string $path)` — `parse_ini_file` returned false.
- `UnresolvableAppCodeException::forDisplayName(string $displayName)` — display name not in the map.

Named static constructors on exceptions make stack traces immediately readable in logs.

### Unrecognised tag handling

Records with unrecognised tags are not dropped. The valid tags are classified normally, and unrecognised tags are collected into `ClassifiedTags::$unrecognizedTags`. `RecordTransformer` logs a PSR-3 warning via the injected `LoggerInterface` when this array is non-empty:

```php
$this->logger->warning('Unrecognized tags encountered', [
    'deviceToken'      => $record->deviceToken,
    'app'              => $record->app,
    'unrecognizedTags' => $classifiedTags->unrecognizedTags,
]);
```

This gives operators visibility into data drift from the third-party service without silently losing records.

## Laravel Zero

[Laravel Zero](https://laravel-zero.com) provides the CLI scaffold — Artisan command registration, service provider loading, configuration, and environment variable support — with none of the HTTP or database stack. All Laravel Zero infrastructure is hidden behind interfaces, so the core domain logic has no framework dependency.

The `AppServiceProvider` binds all interface-to-implementation pairs in one place. `ParseCommand` resolves its dependencies through constructor injection. `TagClassifier` is wired with its three strategies by the service provider rather than through the container, keeping the instantiation explicit and easy to follow.

## Laravel Prompts

[Laravel Prompts](https://laravel.com/docs/13.x/prompts) powers the interactive experience when arguments are omitted.

```
 ┌ 🤐 Source directory or .zip file ──────────────────────────────────────────┐
 │ parser_test.zip                                                            │
 └ Root directory or .zip file containing date subdirectories with .log files ┘

 ┌ 📁 Output CSV file ────────────────────────────────────────────────────────┐
 │ output.csv                                                                 │
 └ Path to write the output CSV file ─────────────────────────────────────────┘

 🌀 Processing log files ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  74/74
 Classifying device tokens and writing CSV...
```

Each prompt is pre-filled with the value from `config/parser.php` (driven by `.env`). Inline `validate` callbacks catch bad paths before the pipeline starts. When arguments are passed directly on the command line, the same validation runs as a post-check using `error()` from Laravel Prompts.

The progress bar uses `progress()` with a known step count (the file list), advances once per file, and shows a hint line below it.

## ZIP support

Passing a `.zip` file as the source triggers automatic extraction to `./tmp` before scanning. After the CSV is written, `./tmp` is deleted recursively. If the INI file is bundled inside the zip, it is detected at `./tmp/{iniPath}` and used automatically — no manual path override required.

The `InteractsWithFiles` trait on `ParseCommand` owns the `extractZip()`, `deleteDirectory()`, and `isValidSource()` methods, keeping file-handling concerns separate from the command's orchestration logic and making them reusable by any future command.

## Configuration

All runtime defaults live in `config/parser.php` and are driven by `.env`:

| Config key            | Env var              | Default                        |
|-----------------------|----------------------|-------------------------------|
| `default_source_path` | `PARSER_SOURCE_PATH` | `parser_test.zip`             |
| `default_output_path` | `PARSER_OUTPUT_PATH` | `output.csv`                  |
| `default_ini_path`    | `PARSER_INI_PATH`    | `parser_test/appCodes.ini`    |
| `headers`             | —                    | Seven-column CSV header array |

CSV headers are injected into `CsvWriter` through the service provider, so column names can be changed without touching any service class.

## Output CSV format

| Column                               | Source                                         |
|--------------------------------------|------------------------------------------------|
| `id`                                 | Sequential integer starting at 1              |
| `appCode`                            | Kebab-case key from `appCodes.ini`            |
| `deviceId`                           | Raw `deviceToken` field                       |
| `contactable`                        | `1` if `deviceTokenStatus` is `"1"`, else `0` |
| `subscription_status`                | `SubscriptionStatus` enum value               |
| `has_downloaded_free_product_status` | `FreeProductDownloadStatus` enum value        |
| `has_downloaded_iap_product_status`  | `InAppPurchaseProductDownloadStatus` enum value|

## Tests

The test suite is written in [Pest](https://pestphp.com/) with [Mockery](https://github.com/mockery/mockery).

Run all tests:

```bash
composer test
```

Unit tests cover every layer in isolation:

- **Enums** — `tryFromTag()` happy paths, null returns, cross-group non-matches, backing values.
- **DTOs** — `fromArray()`, `toArray()`, `fromCsvRow()`, `toCsvRow()`, enum resolution.
- **Tag strategies** — each strategy matches only its own tags and returns the correct default.
- **TagClassifier** — bracket stripping, mismatched brackets, first-match-wins per group, unrecognised tag collection.
- **IniAppCodeResolver** — known display names, special characters, duplicate key handling, both exception types.
- **RecursiveDirectoryScanner** — `.log`-only filtering, nested directory traversal, generator return type.
- **LogFileReader** — header skipping, column mapping, empty field handling, bracket preservation, generator return type.
- **CsvWriter** — header row, record row values, multi-record ordering, write-before-open exception.
- **RecordTransformer** — full transformation, `Contactable` mapping, PSR-3 warning on unrecognised tags, ID passthrough.

Feature tests drive the full pipeline end-to-end through `ParseCommand`, asserting CSV headers, record count, app code resolution, sequential IDs, contactable mapping, tag classification, and summary output.

## Code Quality

The project uses Laravel Pint for formatting with the `per` preset and additional rules in `pint.json`. Static analysis runs via PHPStan at level 9 with `phpstan-strict-rules` enabled.

Run the full quality gate:

```bash
composer check
```

`composer check` runs Pint formatting checks, PHPStan analysis, and the Pest test suite in sequence.

Individual commands:

```bash
composer format:test   # Check formatting only
composer analyse       # Static analysis only
composer test          # Tests only
```

## How I Used AI

I used AI as a collaborator throughout this project.

AI was used for:

- Claude Code as a pair programmer inside the IDE — I directed the architecture, patterns, and design decisions, and used it to move through implementation steps quickly.
- Generating README content from my own notes, code, and goals as the driver.
- Problem solving and rubber ducking through edge cases in the real data (bracket-wrapped tags, duplicate INI keys, unrecognised tag handling).

The strategy pattern, enum design, generator-based I/O, readonly DTO approach, and exception naming were all my own choices. I used AI to implement them faster and to catch static analysis issues early, not to decide what to build.

## AI Classification I considered

Given that the role centres on AI prototyping, I thought about applying AI-based classification to the unknown tag problem rather than relying purely on rule-based strategies.

The idea was to treat unrecognised tags as free-text input and send them through a text-classification API or a locally hosted model such as BERT or GPT. Instead of maintaining an explicit mapping for every possible tag string, you supply the model with a prompt along the lines of:

> "This tag comes from a device token log file. Decide whether it relates to subscription status, free product download status, in-app purchase download status, or none of the above."

A handful of labelled examples alongside the prompt is enough for zero-shot or few-shot classification — the model infers the category without a large training set. The model's response would then populate the three output status fields, with ambiguous results flagged for human review rather than silently defaulted to `unknown`.

This approach has real advantages over rigid rule matching:

- It adapts to new tag strings from the third-party service without requiring a code or config change.
- It handles typos, abbreviations, and novel compound tags that a strategy-based classifier would miss.
- Classification confidence scores give a natural hook for a human-review queue.

I decided against implementing it for this test for a few reasons. The dataset is 73,000 records but the tag vocabulary is small and well-defined — the rule-based Strategy Pattern is predictable, testable, and deterministic for this scale. Introducing an API call or local model inference per unrecognised tag would add latency, cost, external dependencies, and non-determinism that are not justified when the existing tag set is stable enough to be fully covered by enums. The `unrecognizedTags` field on `ClassifiedTags` and the PSR-3 warning on `RecordTransformer` were deliberately designed as the extension point where an AI classifier could slot in later if the tag vocabulary grows or becomes less predictable.

## Addendum

With more time, there are a few areas I would extend:

- Add a `--dry-run` flag that validates the source and reports a record count and unrecognised tag summary without writing a CSV.
- Add a dedicated log channel for unrecognised tag warnings so operators can pipe them to a separate file or alerting service without filtering the main log.
- Extend the ZIP support to handle nested archives or multi-level directory structures that differ from the expected date-subdirectory layout.
- Add a `--format` option to support additional output formats (JSON, NDJSON) by swapping the writer implementation through the existing `CsvWriterInterface` contract.
- Explore a `ParsedRecord` event that could be dispatched per record, allowing downstream listeners to run side effects (metrics, alerting, secondary writes) without coupling them into the command.
