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
    | Registered diagnostic check classes executed when running db:health.
    | You can add your own custom check classes implementing CheckInterface.
    |
    */
    'checks' => [
        LaravelDbTools\LaravelDatabaseHealth\Checks\DatabaseConnectionCheck::class,
        LaravelDbTools\LaravelDatabaseHealth\Checks\QueryResponseTimeCheck::class,
        LaravelDbTools\LaravelDatabaseHealth\Checks\WriteLatencyCheck::class,
        LaravelDbTools\LaravelDatabaseHealth\Checks\DatabaseSizeCheck::class,
        LaravelDbTools\LaravelDatabaseHealth\Checks\TableSizeCheck::class,
        LaravelDbTools\LaravelDatabaseHealth\Checks\ActiveConnectionsCheck::class,
        LaravelDbTools\LaravelDatabaseHealth\Checks\TableFragmentationCheck::class,
        LaravelDbTools\LaravelDatabaseHealth\Checks\MissingIndexCheck::class,
        LaravelDbTools\LaravelDatabaseHealth\Checks\LongRunningQueriesCheck::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Performance, size, and connection thresholds for warning/critical alerts.
    |
    */
    'thresholds' => [
        // Connection & query latency (milliseconds)
        'connection_latency_warning_ms' => env('DB_HEALTH_CONN_WARN_MS', 150.0),
        'query_latency_warning_ms' => env('DB_HEALTH_QUERY_WARN_MS', 50.0),
        'query_latency_critical_ms' => env('DB_HEALTH_QUERY_CRIT_MS', 250.0),
        'write_latency_warning_ms' => env('DB_HEALTH_WRITE_WARN_MS', 100.0),
        'write_latency_critical_ms' => env('DB_HEALTH_WRITE_CRIT_MS', 500.0),

        // Connection pool usage percentage
        'connection_pool_warning_percent' => env('DB_HEALTH_POOL_WARN_PCT', 75.0),
        'connection_pool_critical_percent' => env('DB_HEALTH_POOL_CRIT_PCT', 90.0),

        // Storage & optimization thresholds
        'table_fragmentation_warning_percent' => env('DB_HEALTH_FRAG_WARN_PCT', 25.0),
        'long_query_threshold_seconds' => env('DB_HEALTH_LONG_QUERY_SEC', 5.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automated Notifications & Alerts
    |--------------------------------------------------------------------------
    |
    | Send notifications via mail or webhooks when database health is degraded.
    |
    */
    'notifications' => [
        'enabled' => env('DB_HEALTH_NOTIFICATIONS_ENABLED', false),
        'channels' => ['mail'],
        'recipients' => array_filter(explode(',', env('DB_HEALTH_ALERT_EMAILS', ''))),
    ],
];
