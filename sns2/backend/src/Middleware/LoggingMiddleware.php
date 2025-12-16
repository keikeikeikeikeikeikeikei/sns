<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class LoggingMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Process the request first to ensure we have auth info if available
        // Note: In Slim/PSR-15, if we want to log AFTER handling (to get response status), we do this:
        $response = $handler->handle($request);

        // Get user info from attribute (set by JwtMiddleware)
        $userId = $request->getAttribute('user_id');
        $userStr = $userId ? "User ID: {$userId}" : "Guest";

        // Get request body
        $body = $request->getParsedBody();
        // Mask passwords
        if (is_array($body)) {
            array_walk_recursive($body, function (&$value, $key) {
                if (stripos($key, 'password') !== false) {
                    $value = '********';
                }
            });
            $bodyStr = json_encode($body, JSON_UNESCAPED_UNICODE);
        } else {
            $bodyStr = '(Body not parsable or empty)';
        }

        $method = $request->getMethod();
        $uri = (string) $request->getUri();
        $status = $response->getStatusCode();
        $timestamp = date('Y-m-d H:i:s');

        // Log to stdout
        $logMessage = sprintf(
            "[%s] %s %s (%s) - Status: %d - Body: %s" . PHP_EOL,
            $timestamp,
            $method,
            $uri,
            $userStr,
            $status,
            $bodyStr
        );

        file_put_contents('php://stdout', $logMessage);

        return $response;
    }
}
