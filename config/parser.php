<?php

declare(strict_types=1);

return [

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
