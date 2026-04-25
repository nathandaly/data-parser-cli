<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Source Directory
    |--------------------------------------------------------------------------
    |
    | The default root directory to scan for .log files when no source
    | argument is provided. Can be set via PARSER_SOURCE_PATH in the .env file.
    |
    */

    'default_source_path' => env('PARSER_SOURCE_PATH', 'parser_test'),

    /*
    |--------------------------------------------------------------------------
    | Default Output File Path
    |--------------------------------------------------------------------------
    |
    | The default path for the output CSV file when no output argument
    | is provided. Can be set via PARSER_OUTPUT_PATH in the .env file.
    |
    */

    'default_output_path' => env('PARSER_OUTPUT_PATH', 'output.csv'),

    /*
    |--------------------------------------------------------------------------
    | Default INI File Path
    |--------------------------------------------------------------------------
    |
    | The path to the appCodes.ini file used to resolve app display names
    | to their kebab-case codes. Can be overridden at runtime with --ini.
    |
    */

    'default_ini_path' => env('PARSER_INI_PATH', 'parser_test/appCodes.ini'),

    /*
    |--------------------------------------------------------------------------
    | CSV Output Headers
    |--------------------------------------------------------------------------
    |
    | The column headers written to the first row of the output CSV file.
    | Order here must match the column order produced by OutputRecord::toCsvRow().
    |
    */

    'headers' => [
        'id',
        'appCode',
        'deviceId',
        'contactable',
        'subscription_status',
        'has_downloaded_free_product_status',
        'has_downloaded_iap_product_status',
    ],

];
