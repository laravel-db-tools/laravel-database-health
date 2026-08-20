# Laravel Database Health

Database diagnostics, performance insights, and health checks for Laravel applications.

## Requirements

- PHP 8.2 or higher
- Laravel 10.x, 11.x, or 12.x

## Installation

You can install the package via Composer:

```bash
composer require laravel-db-tools/laravel-database-health
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="database-health-config"
```

## Usage

Run the primary health check command:

```bash
php artisan db:health
```

## Testing

Run tests via PHPUnit:

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
