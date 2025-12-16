<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Blog', description: 'ブログ（長文記事）')]
class BlogController
{
    private const MAX_CONTENT_LENGTH = 10000;

    #[OA\Get(
        path: '/blogs',
        summary: 'ブログ一覧取得',
        tags: ['Blog'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
        ]
    )]
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($params['per_page'] ?? 20)));

        $blogs = Post::blog()
            ->latest()
            ->with(['user', 'reactions'])
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $total = Post::blog()->count();

        return $this->jsonResponse($response, [
            'data' => $blogs->map(fn($blog) => $this->formatBlog($blog)),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    #[OA\Post(
        path: '/blogs',
        summary: 'ブログ投稿',
        tags: ['Blog'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255),
                    new OA\Property(property: 'content', type: 'string', maxLength: 10000),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '投稿成功'),
            new OA\Response(response: 400, description: 'バリデーションエラー'),
        ]
    )]
    public function store(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $data = $request->getParsedBody();

        $errors = [];
        if (empty($data['title']) || mb_strlen($data['title']) > 255) {
            $errors[] = 'Title is required and must be 255 characters or less';
        }
        if (empty($data['content'])) {
            $errors[] = 'Content is required';
        } elseif (mb_strlen($data['content']) > self::MAX_CONTENT_LENGTH) {
            $errors[] = 'Content must be ' . self::MAX_CONTENT_LENGTH . ' characters or less';
        }

        if (!empty($errors)) {
            return $this->jsonResponse($response, ['errors' => $errors], 400);
        }

        $blog = Post::create([
            'user_id' => $userId,
            'type' => Post::TYPE_BLOG,
            'title' => $data['title'],
            'content_long' => $data['content'],
        ]);

        $blog->load('user');

        return $this->jsonResponse($response, $this->formatBlog($blog, true), 201);
    }

    #[OA\Get(
        path: '/blogs/{id}',
        summary: 'ブログ詳細取得',
        tags: ['Blog'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
            new OA\Response(response: 404, description: '見つからない'),
        ]
    )]
    public function show(Request $request, Response $response, array $args): Response
    {
        $blog = Post::blog()
            ->with(['user', 'reactions', 'quotesAsSource.quotingPost.user'])
            ->find($args['id']);

        if (!$blog) {
            return $this->jsonResponse($response, ['error' => 'Blog not found'], 404);
        }

        return $this->jsonResponse($response, $this->formatBlog($blog, true));
    }

    #[OA\Put(
        path: '/blogs/{id}',
        summary: 'ブログ更新',
        tags: ['Blog'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255),
                    new OA\Property(property: 'content', type: 'string', maxLength: 10000),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '更新成功'),
            new OA\Response(response: 403, description: '権限なし'),
            new OA\Response(response: 404, description: '見つからない'),
        ]
    )]
    public function update(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $blog = Post::blog()->find($args['id']);

        if (!$blog) {
            return $this->jsonResponse($response, ['error' => 'Blog not found'], 404);
        }

        if ($blog->user_id !== $userId) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $data = $request->getParsedBody();
        $updateData = [];

        if (isset($data['title'])) {
            if (mb_strlen($data['title']) > 255) {
                return $this->jsonResponse($response, ['error' => 'Title must be 255 characters or less'], 400);
            }
            $updateData['title'] = $data['title'];
        }

        if (isset($data['content'])) {
            if (mb_strlen($data['content']) > self::MAX_CONTENT_LENGTH) {
                return $this->jsonResponse($response, [
                    'error' => 'Content must be ' . self::MAX_CONTENT_LENGTH . ' characters or less'
                ], 400);
            }
            $updateData['content_long'] = $data['content'];
        }

        if (!empty($updateData)) {
            $blog->update($updateData);
        }

        $blog->load('user');

        return $this->jsonResponse($response, $this->formatBlog($blog, true));
    }

    #[OA\Delete(
        path: '/blogs/{id}',
        summary: 'ブログ削除',
        tags: ['Blog'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: '削除成功'),
            new OA\Response(response: 403, description: '権限なし'),
            new OA\Response(response: 404, description: '見つからない'),
        ]
    )]
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $blog = Post::blog()->find($args['id']);

        if (!$blog) {
            return $this->jsonResponse($response, ['error' => 'Blog not found'], 404);
        }

        if ($blog->user_id !== $userId) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $blog->delete();

        return $response->withStatus(204);
    }

    private function formatBlog(Post $blog, bool $fullContent = false): array
    {
        $data = [
            'id' => $blog->id,
            'type' => $blog->type,
            'title' => $blog->title,
            'content' => $fullContent ? $blog->content_long : mb_substr($blog->content_long ?? '', 0, 200) . '...',
            'user' => [
                'id' => $blog->user->id,
                'username' => $blog->user->username,
                'display_name' => $blog->user->display_name,
                'avatar_url' => $blog->user->avatar_url,
            ],
            'reaction_counts' => $blog->reaction_counts,
            'created_at' => $blog->created_at->toISOString(),
            'updated_at' => $blog->updated_at->toISOString(),
        ];

        if ($fullContent && $blog->relationLoaded('quotesAsSource')) {
            $data['quoted_by'] = $blog->quotesAsSource->map(fn($quote) => [
                'id' => $quote->quotingPost->id,
                'content' => $quote->quotingPost->content_short,
                'user' => [
                    'username' => $quote->quotingPost->user->username,
                    'display_name' => $quote->quotingPost->user->display_name,
                ],
            ]);
        }

        return $data;
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
