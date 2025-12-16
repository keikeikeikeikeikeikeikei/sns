<?php
// Test rate limiting by making multiple requests

echo "=== Rate Limit Test ===\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/api/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'test@example.com', 'password' => 'wrong']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

for ($i = 1; $i <= 12; $i++) {
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Extract rate limit headers
    preg_match('/X-RateLimit-Remaining: (\d+)/', $response, $remaining);
    preg_match('/X-RateLimit-Limit: (\d+)/', $response, $limit);

    $remainingVal = $remaining[1] ?? 'N/A';
    $limitVal = $limit[1] ?? 'N/A';

    echo "Request $i: HTTP $httpCode | Remaining: $remainingVal / $limitVal\n";

    if ($httpCode === 429) {
        echo ">>> Rate limit exceeded! <<<\n";
        break;
    }
}

curl_close($ch);
