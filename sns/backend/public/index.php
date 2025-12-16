<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../src/Controllers/PostController.php';
require_once __DIR__ . '/../src/Controllers/UserController.php';
require_once __DIR__ . '/../src/Controllers/ReactionController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Helper to get JSON input
function getJsonInput()
{
    $input = json_decode(file_get_contents('php://input'), true);
    return is_array($input) ? $input : [];
}

// Router
if ($uri === '/' || $uri === '/api' || $uri === '/api/') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'message' => 'SNS API is running']);
    exit;
}

if ($uri === '/api/register' && $method === 'POST') {
    header('Content-Type: application/json');
    $controller = new UserController();
    echo $controller->register(getJsonInput());
    exit;
}

if ($uri === '/api/login' && $method === 'POST') {
    header('Content-Type: application/json');
    $controller = new UserController();
    echo $controller->login(getJsonInput());
    exit;
}

if ($uri === '/api/posts' && $method === 'GET') {
    header('Content-Type: application/json');
    $controller = new PostController();
    $type = $_GET['type'] ?? null;
    echo $controller->index($type);
    exit;
}

if ($uri === '/api/posts' && $method === 'POST') {
    header('Content-Type: application/json');
    $controller = new PostController();
    // Handle specific file upload + data
    // PostController::store expects array with 'type', 'content', 'title', 'quoted_post_id'
    // For multipart/form-data, $_POST is populated. For JSON, getJsonInput()
    $data = !empty($_POST) ? $_POST : getJsonInput();
    echo $controller->store($data);
    exit;
}

if ($uri === '/api/reactions' && $method === 'POST') {
    header('Content-Type: application/json');
    $controller = new ReactionController();
    echo $controller->store(getJsonInput());
    exit;
}

if ($uri === '/api/posts/best_answer' && $method === 'POST') {
    header('Content-Type: application/json');
    $controller = new PostController();
    echo $controller->setBestAnswer();
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not Found', 'uri' => $uri]);
