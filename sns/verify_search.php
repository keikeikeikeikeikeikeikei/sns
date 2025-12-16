<?php

$baseUrl = 'http://localhost:8000/api';

function getPosts($token, $q = null)
{
    global $baseUrl;
    $url = $baseUrl . "/posts?type=microblog";
    if ($q) {
        $url .= "&q=" . urlencode($q);
    }

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

echo "--- Search Verification ---\n";

// 1. Login
$authUrl = $baseUrl . '/login';
$opts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/json", 'content' => json_encode(['username' => 'testuser', 'password' => 'password123'])]];
$res = file_get_contents($authUrl, false, stream_context_create($opts));
$token = json_decode($res, true)['token'] ?? null;

if (!$token)
    die("Login failed.\n");

// 2. Create Unique Post
$uniqueString = "UniqSearchTerm" . rand(1000, 9999);
$content = "This is a post with $uniqueString in it.";
$postUrl = $baseUrl . '/posts';
$opts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\nAuthorization: Bearer $token\r\n", 'content' => json_encode(['type' => 'microblog', 'content' => $content])]];
file_get_contents($postUrl, false, stream_context_create($opts));

// 3. Search for it
echo "Searching for '$uniqueString'...\n";
$results = getPosts($token, $uniqueString);
if (count($results) >= 1 && strpos($results[0]['content'], $uniqueString) !== false) {
    echo "PASS: Found post via search.\n";
} else {
    echo "FAIL: Could not find post with unique string.\n";
}

// 4. Search for non-existent
echo "Searching for 'NonExistentStringXYZ'...\n";
$results = getPosts($token, 'NonExistentStringXYZ');
if (count($results) === 0) {
    echo "PASS: Correctly returned empty for no matches.\n";
} else {
    echo "FAIL: Returned results unexpectedly.\n";
}
