# Laravel Database Health

Production-ready database diagnostics, performance metrics, table storage audits, connection pool monitoring, and health checks for Laravel applications.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laravel-db-tools/laravel-database-health.svg)](https://packagist.org/packages/laravel-db-tools/laravel-database-health)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel-db-tools/laravel-database-health.svg)](https://packagist.org/packages/laravel-db-tools/laravel-database-health)
[![License](https://img.shields.io/packagist/l/laravel-db-tools/laravel-database-health.svg)](LICENSE.md)

---

## Supported Databases & PHP Versions

- **Database Engines**: MySQL 5.7 / 8.x / MariaDB, PostgreSQL 12+, SQLite 3, SQL Server
- **PHP**: ^8.2 | ^8.3
- **Laravel**: 10.x | 11.x | 12.x

---

## Installation

Install the package via Composer:

```bash
composer require laravel-db-tools/laravel-database-health
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="database-health-config"
```

---

## Artisan Commands

### 1. Primary Health Diagnostics (`db:health`)

Run all configured database health checks and display formatted results:

```bash
# Run all diagnostics on default connection
php artisan db:health

# Check a specific connection
php artisan db:health --connection=mysql_analytics

# Run specific checks only
php artisan db:health --check=Connectivity --check=Query

# Output structured JSON (ideal for Datadog / Grafana / CI/CD / monitoring scripts)
php artisan db:health --json

# Fail with non-zero exit code on warning (for CI pipelines)
php artisan db:health --fail-on-warning
```

---

### 2. Table Storage & Fragmentation Breakdown (`db:health:tables`)

Inspect table sizes, row counts, and fragmented disk space:

```bash
php artisan db:health:tables

# Limit output to top 10 largest tables
php artisan db:health:tables --limit=10

# Output as JSON
php artisan db:health:tables --json
```

---

### 3. Connection Pool & Threads (`db:health:connections`)

Inspect active connection threads and utilization against server limits:

```bash
php artisan db:health:connections
```

---

### 4. Foreign Key Index Audit (`db:health:indexes`)

Scan database tables for unindexed foreign keys (`*_id`) causing full table scans during joins:

```bash
php artisan db:health:indexes
```

---

### 5. Table Optimization & Vacuum (`db:health:optimize`)

Defragment tables and reclaim unused disk space:

```bash
# Interactive table optimization
php artisan db:health:optimize

# Optimize a specific table directly
php artisan db:health:optimize --table=orders --force

# Target tables with >= 20% fragmentation
php artisan db:health:optimize --threshold=20 --force
```

---

## Programmatic Usage & Facade

You can trigger health checks within your code, API controllers, or custom scheduled tasks:

```php
use LaravelDbTools\LaravelDatabaseHealth\Facades\DatabaseHealth;

// Run checks on default connection
$results = DatabaseHealth::check();

foreach ($results as $result) {
    echo $result->checkName . ': ' . $result->status->value . ' (' . $result->durationMs . 'ms)';
}
```

---

## Events & Automated Alerts

The package automatically dispatches Laravel events on check execution:

- `DatabaseHealthChecked`: Dispatched after every check run.
- `DatabaseHealthDegraded`: Dispatched when warning threshold is reached.
- `DatabaseHealthFailed`: Dispatched on critical diagnostic failures.

### Configuring Email Alerts (`config/database-health.php`)

```php
'notifications' => [
    'enabled' => env('DB_HEALTH_NOTIFICATIONS_ENABLED', true),
    'channels' => ['mail'],
    'recipients' => ['devops@example.com', 'admin@example.com'],
],
```

---

## Testing

Run tests via Composer:

```bash
composer test
```

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more details.
