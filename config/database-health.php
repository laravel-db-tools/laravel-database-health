<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    |
    | Specify the database connection to monitor. If set to null, the default
    | connection defined in config/database.php will be used.
    |
    */
    'connection' => env('DB_HEALTH_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Health Checks Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific diagnostics and health checks when running
    | the db:health Artisan command.
    |
    */
    'checks' => [
        // Diagnostics will be configured here in upcoming tasks
    ],

    /*
    |--------------------------------------------------------------------------
    | Thresholds
    |--------------------------------------------------------------------------
    |
    | Define warning and critical alert thresholds for health diagnostics.
    |
    */
    'thresholds' => [
        // Threshold configurations will be added here
    ],
];
