<?php

use Illuminate\Http\Request;

// Suppress Deprecated warnings for PHP 8.5 compatibility
error_reporting(E_ALL & ~E_DEPRECATED);

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../laravel/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Auto Loader...
require __DIR__ . '/../laravel/vendor/autoload.php';

// Bootstrap the Framework...
(require __DIR__ . '/../laravel/bootstrap/app.php')
    ->handleRequest(Request::capture());
