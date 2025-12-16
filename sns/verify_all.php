<?php

$baseUrl = 'http://localhost:8000/api';
$username = 'verifier_' . rand(1000, 9999);
$password = 'pass123';

echo "=== Backend Validation Script ===\n";

// 1. Register
echo "[1] Registering User ($username)...\n";
$regOpts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/json", 'content' => json_encode(['username' => $username, 'password' => $password])]];
file_get_contents($baseUrl . '/register', false, stream_context_create($regOpts));

// 2. Login
echo "[2] Logging in...\n";
$loginOpts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/json", 'content' => json_encode(['username' => $username, 'password' => $password])]];
$res = file_get_contents($baseUrl . '/login', false, stream_context_create($loginOpts));
$data = json_decode($res, true);
$token = $data['token'] ?? null;

if (!$token)
    die("FATAL: Login failed.\n");
echo "    Success! Token: " . substr($token, 0, 10) . "...\n";

// 3. Post Text (Microblog)
echo "[3] Posting Text (Microblog)...\n";
$postOpts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\nAuthorization: Bearer $token\r\n", 'content' => json_encode(['type' => 'microblog', 'content' => 'Hello World from PHP Script'])]];
file_get_contents($baseUrl . '/posts', false, stream_context_create($postOpts));
echo "    Posted.\n";

// 4. Image Upload via CURL Check
echo "[4] Testing Image Upload via CURL...\n";
// We use shell_exec to run actual curl command as requested
$cmd = sprintf(
    'curl -s -X POST -H "Authorization: Bearer %s" -F "image=@test_image.png" -F "type=microblog" -F "content=ImagePostTest" "%s/posts"',
    $token,
    $baseUrl
);
$output = shell_exec($cmd);
echo "    CURL Output: " . substr($output, 0, 100) . "...\n";

if (strpos($output, '"image_path":') !== false || strpos($output, 'success') !== false || strpos($output, 'id') !== false) {
    echo "    Image Upload seems SUCCESSFUL.\n";
} else {
    echo "    Image Upload verification unsure (check output).\n";
}

// 5. Verify Feed & Search
echo "[5] Verifying Search...\n";
$searchOpts = ['http' => ['method' => 'GET', 'header' => "Content-Type: application/json\r\nAuthorization: Bearer $token\r\n"]];
$searchRes = file_get_contents($baseUrl . '/posts?q=ImagePostTest', false, stream_context_create($searchOpts));
$searchResults = json_decode($searchRes, true);

if (count($searchResults) > 0) {
    echo "    PASS: Found uploaded image post via search.\n";
    echo "    Image Path: " . ($searchResults[0]['image_path'] ?? 'N/A') . "\n";
} else {
    echo "    FAIL: Could not find post.\n";
}

echo "=== Backend Validation Complete ===\n";
