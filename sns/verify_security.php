<?php

$baseUrl = 'http://localhost:8000/api';

function makeRequest($endpoint, $method = 'GET', $data = null, $token = null)
{
    global $baseUrl;
    $url = $baseUrl . $endpoint;

    $opts = [
        'http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\n",
            'ignore_errors' => true
        ]
    ];

    if ($data) {
        $opts['http']['content'] = json_encode($data);
    }

    if ($token) {
        $opts['http']['header'] .= "Authorization: Bearer $token\r\n";
    }

    $context = stream_context_create($opts);
    $result = file_get_contents($url, false, $context);
    $headers = $http_response_header;

    // Parse status code
    preg_match('#HTTP/\d\.\d (\d+)#', $headers[0], $matches);
    $statusCode = intval($matches[1]);

    return ['status' => $statusCode, 'body' => $result];
}

echo "--- Security Verification Test ---\n";

// 1. Try to access posts without login
echo "1. Testing access without token (Should fail)...\n";
$res = makeRequest('/posts');
if ($res['status'] === 401 || $res['status'] === 403) {
    echo "PASS: Access denied as expected (Status: " . $res['status'] . ")\n";
} else {
    echo "FAIL: Access allowed unexpectedly! (Status: " . $res['status'] . ")\n";
}

// 2. Register new test user
echo "\n2. Registering new test user...\n";
$username = 'authtest_' . time();
$password = 'secret';
$res = makeRequest('/register', 'POST', ['username' => $username, 'password' => $password]);
if ($res['status'] === 201) {
    echo "PASS: User registered ($username)\n";
} else {
    echo "FAIL: Registration failed. " . $res['body'] . "\n";
    exit;
}

// 3. Login
echo "\n3. Logging in...\n";
$res = makeRequest('/login', 'POST', ['username' => $username, 'password' => $password]);
$data = json_decode($res['body'], true);
$token = $data['token'] ?? null;

if ($res['status'] === 200 && $token) {
    echo "PASS: Login successful. Token received.\n";
} else {
    echo "FAIL: Login failed. " . $res['body'] . "\n";
    exit;
}

// 4. Access with token
echo "\n4. Testing access WITH token...\n";
$res = makeRequest('/posts', 'GET', null, $token);
if ($res['status'] === 200) {
    echo "PASS: Access granted with valid token.\n";
    echo "Data sample: " . substr($res['body'], 0, 50) . "...\n";
} else {
    echo "FAIL: Access denied with valid token! (Status: " . $res['status'] . ")\n";
}

// 5. Create a post
echo "\n5. Creating a post with token...\n";
$res = makeRequest('/posts', 'POST', ['type' => 'microblog', 'content' => 'Security Test'], $token);
if ($res['status'] === 201) {
    echo "PASS: Post created.\n";
} else {
    echo "FAIL: Post creation failed. " . $res['body'] . "\n";
}
