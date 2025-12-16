<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule();

$connection = $_ENV['DB_CONNECTION'] ?? 'sqlite';

if ($connection === 'sqlite') {
    $capsule->addConnection([
        'driver' => 'sqlite',
        'database' => __DIR__ . '/../' . ($_ENV['DB_DATABASE'] ?? 'database/database.sqlite'),
        'prefix' => '',
    ]);
} else {
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? 'sns2',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]);
}

$capsule->setAsGlobal();
$capsule->bootEloquent();
