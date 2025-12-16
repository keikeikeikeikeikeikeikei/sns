<?php

declare(strict_types=1);

use Slim\App;
use App\Controllers\AuthController;
use App\Controllers\FeedController;
use App\Controllers\QaController;
use App\Controllers\BlogController;
use App\Controllers\ReactionController;
use App\Controllers\SearchController;
use App\Controllers\UploadController;
use App\Middleware\JwtMiddleware;
use App\Middleware\RateLimitMiddleware;

/** @var App $app */

// Health check
$app->get('/api/health', function ($request, $response) {
    $response->getBody()->write(json_encode(['status' => 'ok']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Auth routes (public) - Stricter rate limit: 10 requests per minute
$app->post('/api/auth/register', [AuthController::class, 'register'])
    ->add(new RateLimitMiddleware(10, 60, 'auth'));
$app->post('/api/auth/login', [AuthController::class, 'login'])
    ->add(new RateLimitMiddleware(10, 60, 'auth'));

// Protected routes - Standard rate limit: 100 requests per minute
$app->group('/api', function ($group) {
    // User
    $group->get('/me', [AuthController::class, 'me']);

    // Search
    $group->get('/search', [SearchController::class, 'search']);

    // Upload
    $group->post('/upload', [UploadController::class, 'upload']);
    $group->post('/upload/multiple', [UploadController::class, 'uploadMultiple']);

    // Feeds (つぶやき)
    $group->get('/feeds', [FeedController::class, 'index']);
    $group->post('/feeds', [FeedController::class, 'store']);
    $group->get('/feeds/{id}', [FeedController::class, 'show']);
    $group->put('/feeds/{id}', [FeedController::class, 'update']);
    $group->delete('/feeds/{id}', [FeedController::class, 'destroy']);

    // Q&A
    $group->get('/qa', [QaController::class, 'index']);
    $group->post('/qa', [QaController::class, 'store']);
    $group->get('/qa/{id}', [QaController::class, 'show']);
    $group->put('/qa/{id}', [QaController::class, 'update']);
    $group->delete('/qa/{id}', [QaController::class, 'destroy']);
    $group->post('/qa/{id}/answers', [QaController::class, 'storeAnswer']);
    $group->put('/qa/{id}/best-answer', [QaController::class, 'setBestAnswer']);

    // Blogs
    $group->get('/blogs', [BlogController::class, 'index']);
    $group->post('/blogs', [BlogController::class, 'store']);
    $group->get('/blogs/{id}', [BlogController::class, 'show']);
    $group->put('/blogs/{id}', [BlogController::class, 'update']);
    $group->delete('/blogs/{id}', [BlogController::class, 'destroy']);

    // Quotes (汎用 - 任意の投稿を引用)
    $group->post('/posts/{id}/quotes', [FeedController::class, 'createQuote']);

    // Reactions (汎用 - 任意の投稿にリアクション)
    $group->get('/posts/{id}/reactions', [ReactionController::class, 'index']);
    $group->post('/posts/{id}/reactions', [ReactionController::class, 'store']);
    $group->delete('/posts/{id}/reactions/{emoji}', [ReactionController::class, 'destroy']);
})
    ->add(new RateLimitMiddleware(100, 60, 'api'))
    ->add(new JwtMiddleware());



