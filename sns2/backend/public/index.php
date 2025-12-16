<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create App
$app = AppFactory::create();

// Setup error logger
$errorLogger = new Logger('sns_2a');
$logPath = __DIR__ . '/../logs/error.log';
$logDir = dirname($logPath);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$errorLogger->pushHandler(new RotatingFileHandler($logPath, 30, Logger::ERROR));

// Add Error Middleware - log errors but don't expose details to users
$errorMiddleware = $app->addErrorMiddleware(
    false, // displayErrorDetails - always false to hide from users
    true,
    true
);

// Custom error handler to log stack traces
$errorMiddleware->setDefaultErrorHandler(function ($request, $exception, $displayErrorDetails, $logErrors, $logErrorDetails) use ($errorLogger) {
    // Log the full error with stack trace for analysis
    $errorLogger->error($exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
        'url' => (string) $request->getUri(),
        'method' => $request->getMethod(),
    ]);

    // Return generic error to user (no stack trace exposure)
    $response = new \Slim\Psr7\Response();
    $response->getBody()->write(json_encode([
        'error' => true,
        'message' => 'An internal error occurred',
    ], JSON_UNESCAPED_UNICODE));

    return $response
        ->withStatus(500)
        ->withHeader('Content-Type', 'application/json');
});


// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Add Routing Middleware
$app->addRoutingMiddleware();

// CORS Middleware - configurable origin
$allowedOrigin = $_ENV['CORS_ORIGIN'] ?? '*';
$app->add(function ($request, $handler) use ($allowedOrigin) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Handle preflight requests
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Bootstrap database
require __DIR__ . '/../config/database.php';
use Illuminate\Database\Capsule\Manager as Capsule;

// Log DB Queries to stdout
Capsule::connection()->listen(function ($query) {
    $timestamp = date('Y-m-d H:i:s');
    $sql = $query->sql;
    $bindings = json_encode($query->bindings, JSON_UNESCAPED_UNICODE);
    $time = $query->time; // ms

    $log = sprintf(
        "[%s] [DB] %s | Bindings: %s | Time: %.2fms" . PHP_EOL,
        $timestamp,
        $sql,
        $bindings,
        $time
    );

    file_put_contents('php://stdout', $log);
});

// Register Logging Middleware
$app->add(new \App\Middleware\LoggingMiddleware());

// Register routes
require __DIR__ . '/../config/routes.php';

// Run App
$app->run();

