<?php

$url = 'http://localhost:8000/api/posts?type=microblog';

// We updated the auth, so we need a token for /api/posts. 
// However, the rate limiter is GLOBAL and runs BEFORE routing/auth.
// So we can hit any endpoint, even a 404 one or without auth, and it should still count towards the IP limit.
// Let's try hitting the root API to be safe, or just the login endpoint which doesn't need auth.
$url = 'http://localhost:8000/api/login';

$limit = 65; // Threshold is 60

echo "Testing Rate Limiter (Limit: 60/min)...\n";

for ($i = 1; $i <= $limit; $i++) {
    $context = stream_context_create(['http' => ['ignore_errors' => true, 'method' => 'POST', 'header' => "Content-Type: application/json", 'content' => '{}']]);
    $res = file_get_contents($url, false, $context);

    // Check headers
    $headers = $http_response_header;
    preg_match('#HTTP/\d\.\d (\d+)#', $headers[0], $matches);
    $status = intval($matches[1]);

    if ($status === 429) {
        echo "Request $i: BLOCKED (429 Too Many Requests)\n";
        echo "VERIFICATION PASSED: Rate limit triggered.\n";
        exit(0);
    }

    if ($i % 10 == 0)
        echo "Request $i: OK ($status)\n";
}

echo "VERIFICATION FAILED: Rate limit NOT triggered after $limit requests.\n";
