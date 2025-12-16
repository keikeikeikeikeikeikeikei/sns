<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Rate Limiting Middleware
 * 
 * IPアドレスベースのレート制限を実装
 * SQLiteベースのシンプルな実装（本番ではRedis推奨）
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private string $storageFile;
    private int $maxRequests;
    private int $windowSeconds;
    private string $identifier;

    /**
     * @param int $maxRequests ウィンドウ内の最大リクエスト数
     * @param int $windowSeconds 時間ウィンドウ（秒）
     * @param string $identifier レート制限の識別子（エンドポイントグループ名）
     */
    public function __construct(
        int $maxRequests = 60,
        int $windowSeconds = 60,
        string $identifier = 'default'
    ) {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->identifier = $identifier;
        $this->storageFile = __DIR__ . '/../../database/rate_limits.json';
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $clientIp = $this->getClientIp($request);
        $key = $this->identifier . ':' . $clientIp;

        $rateLimitData = $this->getRateLimitData();
        $now = time();

        // Clean expired entries
        $rateLimitData = $this->cleanExpired($rateLimitData, $now);

        // Get or initialize client's request count
        if (!isset($rateLimitData[$key])) {
            $rateLimitData[$key] = [
                'count' => 0,
                'window_start' => $now,
            ];
        }

        $clientData = $rateLimitData[$key];

        // Check if window has expired
        if (($now - $clientData['window_start']) >= $this->windowSeconds) {
            $clientData = [
                'count' => 0,
                'window_start' => $now,
            ];
        }

        // Check rate limit
        if ($clientData['count'] >= $this->maxRequests) {
            $retryAfter = $this->windowSeconds - ($now - $clientData['window_start']);

            $response = new Response(429);
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
                ->withHeader('X-RateLimit-Remaining', '0')
                ->withHeader('X-RateLimit-Reset', (string) ($clientData['window_start'] + $this->windowSeconds))
                ->withHeader('Retry-After', (string) $retryAfter);

            $response->getBody()->write(json_encode([
                'error' => 'Rate limit exceeded',
                'message' => 'リクエストが多すぎます。しばらくお待ちください。',
                'retry_after' => $retryAfter,
            ]));

            return $response;
        }

        // Increment request count
        $clientData['count']++;
        $rateLimitData[$key] = $clientData;
        $this->saveRateLimitData($rateLimitData);

        // Process request
        $response = $handler->handle($request);

        // Add rate limit headers
        $remaining = $this->maxRequests - $clientData['count'];
        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $remaining))
            ->withHeader('X-RateLimit-Reset', (string) ($clientData['window_start'] + $this->windowSeconds));
    }

    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();

        // Check for forwarded IP (behind proxy/load balancer)
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP'];
        foreach ($headers as $header) {
            if (!empty($serverParams[$header])) {
                $ips = explode(',', $serverParams[$header]);
                return trim($ips[0]);
            }
        }

        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private function getRateLimitData(): array
    {
        if (!file_exists($this->storageFile)) {
            return [];
        }

        // Use file locking to prevent race conditions
        $handle = fopen($this->storageFile, 'r');
        if ($handle === false) {
            return [];
        }

        flock($handle, LOCK_SH); // Shared lock for reading
        $content = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return json_decode($content, true) ?? [];
    }

    private function saveRateLimitData(array $data): void
    {
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Use file locking to prevent race conditions
        $handle = fopen($this->storageFile, 'c'); // Open for writing, create if not exists
        if ($handle === false) {
            return;
        }

        flock($handle, LOCK_EX); // Exclusive lock for writing
        ftruncate($handle, 0);
        fwrite($handle, json_encode($data));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    private function cleanExpired(array $data, int $now): array
    {
        foreach ($data as $key => $value) {
            if (($now - $value['window_start']) >= $this->windowSeconds * 2) {
                unset($data[$key]);
            }
        }
        return $data;
    }
}
