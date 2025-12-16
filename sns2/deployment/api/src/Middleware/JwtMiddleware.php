<?php

declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class JwtMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return $this->unauthorizedResponse('Authorization header missing');
        }

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse('Invalid authorization header format');
        }

        $token = $matches[1];

        try {
            $secret = $_ENV['JWT_SECRET'] ?? null;
            if (empty($secret)) {
                throw new \RuntimeException('JWT_SECRET environment variable not set');
            }
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            // Add user info to request attributes
            $request = $request->withAttribute('user_id', $decoded->sub);
            $request = $request->withAttribute('user', $decoded);

            return $handler->handle($request);
        } catch (\Exception $e) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }
    }

    private function unauthorizedResponse(string $message): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'error' => true,
            'message' => $message,
        ]));

        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}
