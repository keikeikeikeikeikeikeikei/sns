<?php

$baseUrl = 'http://localhost:8000/api';

function getPosts($token, $page, $limit)
{
    global $baseUrl;
    $url = $baseUrl . "/posts?type=microblog&page=$page&limit=$limit";
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer $token\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $result = file_get_contents($url, false, $context);
    return json_decode($result, true);
}

echo "--- Pagination Verification ---\n";

// 1. Login to get token
$authUrl = $baseUrl . '/login';
$opts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/json", 'content' => json_encode(['username' => 'testuser', 'password' => 'password123'])]];
$res = file_get_contents($authUrl, false, stream_context_create($opts));
$token = json_decode($res, true)['token'] ?? null;

if (!$token)
    die("Login failed. Cannot test pagination.\n");

// 2. Create multiple posts to ensure we have data
echo "Creating dummy posts...\n";
$postUrl = $baseUrl . '/posts';
for ($i = 0; $i < 5; $i++) {
    $content = "Pagination Test Post $i - " . microtime(true);
    $opts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\nAuthorization: Bearer $token\r\n", 'content' => json_encode(['type' => 'microblog', 'content' => $content])]];
    file_get_contents($postUrl, false, stream_context_create($opts));
    usleep(100000); // 100ms delay
}

// 3. Test Page 1 (Limit 2)
echo "Fetching Page 1 (Limit 2)...\n";
$page1 = getPosts($token, 1, 2);
$count1 = count($page1);
echo "Page 1 count: $count1\n";

if ($count1 !== 2) {
    echo "FAIL: Expected 2 posts on page 1.\n";
} else {
    echo "PASS: Page 1 returned 2 posts.\n";
}

// 4. Test Page 2 (Limit 2)
echo "Fetching Page 2 (Limit 2)...\n";
$page2 = getPosts($token, 2, 2);
$count2 = count($page2);
echo "Page 2 count: $count2\n";

if ($page1[0]['id'] === $page2[0]['id']) {
    echo "FAIL: Page 1 and Page 2 contain the same first post!\n";
} else {
    echo "PASS: Pagination works (Page 1 and 2 are different).\n";
}
