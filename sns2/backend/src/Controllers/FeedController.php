<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;
use App\Models\Quote;
use App\Helpers\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Feed', description: 'つぶやき（マイクロブログ）')]
class FeedController
{
    #[OA\Get(
        path: '/feeds',
        summary: 'つぶやき一覧取得（新着順）',
        tags: ['Feed'],
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
        $GLOBALS['request_user_id'] = $request->getAttribute('user_id'); // Store for formatPost helper
        $params = $request->getQueryParams();
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($params['per_page'] ?? 20)));

        $feeds = Post::feed()
            ->latest()
            ->with(['user', 'reactions', 'quotesAsQuoting.sourcePost.user'])
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $total = Post::feed()->count();

        return $this->jsonResponse($response, [
            'data' => $feeds->map(fn($feed) => $this->formatPost($feed, true)),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    #[OA\Post(
        path: '/feeds',
        summary: 'つぶやき投稿',
        tags: ['Feed'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', maxLength: 150),
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
        $data = $request->getParsedBody() ?? [];

        // Validation with sanitization
        $validator = new Validator($data);
        $validator
            ->required('content', 'つぶやき内容')
            ->max('content', 150, 'つぶやき内容')
            ->sanitize('content');

        if ($validator->fails()) {
            return $this->jsonResponse($response, ['errors' => $validator->errors()], 400);
        }

        $validated = $validator->validated();

        // Handle image URLs
        $imageUrls = [];
        if (!empty($data['image_urls']) && is_array($data['image_urls'])) {
            $imageUrls = array_slice($data['image_urls'], 0, 4); // max 4 images
        }

        $feed = Post::create([
            'user_id' => $userId,
            'type' => Post::TYPE_FEED,
            'content_short' => $validated['content'],
            'metadata' => json_encode(['image_urls' => $imageUrls]),
        ]);

        $feed->load('user');

        return $this->jsonResponse($response, $this->formatPost($feed), 201);
    }

    #[OA\Get(
        path: '/feeds/{id}',
        summary: 'つぶやき詳細取得',
        tags: ['Feed'],
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
        $GLOBALS['request_user_id'] = $request->getAttribute('user_id'); // Store for formatPost helper
        $feed = Post::feed()
            ->with(['user', 'reactions', 'quotesAsSource.quotingPost.user', 'quotesAsQuoting.sourcePost.user'])
            ->find($args['id']);

        if (!$feed) {
            return $this->jsonResponse($response, ['error' => 'Feed not found'], 404);
        }

        return $this->jsonResponse($response, $this->formatPost($feed, true));
    }

    #[OA\Put(
        path: '/feeds/{id}',
        summary: 'つぶやき更新',
        tags: ['Feed'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', maxLength: 150),
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
        $feed = Post::feed()->find($args['id']);

        if (!$feed) {
            return $this->jsonResponse($response, ['error' => 'Feed not found'], 404);
        }

        if ($feed->user_id !== $userId) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $data = $request->getParsedBody();
        $content = $data['content'] ?? '';

        if (empty($content) || mb_strlen($content) > 150) {
            return $this->jsonResponse($response, [
                'error' => 'Content is required and must be 150 characters or less',
            ], 400);
        }

        $feed->update(['content_short' => $content]);
        $feed->load('user');

        return $this->jsonResponse($response, $this->formatPost($feed, true));
    }

    #[OA\Delete(
        path: '/feeds/{id}',
        summary: 'つぶやき削除',
        tags: ['Feed'],
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
        $feed = Post::feed()->find($args['id']);

        if (!$feed) {
            return $this->jsonResponse($response, ['error' => 'Feed not found'], 404);
        }

        if ($feed->user_id !== $userId) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $feed->delete();

        return $response->withStatus(204);
    }

    #[OA\Post(
        path: '/posts/{id}/quotes',
        summary: '投稿を引用してつぶやきを作成',
        tags: ['Feed'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', maxLength: 150),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '引用投稿成功'),
            new OA\Response(response: 404, description: '引用元が見つからない'),
        ]
    )]
    public function createQuote(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $sourcePostId = (int) $args['id'];
        $data = $request->getParsedBody();

        // Check source post exists
        $sourcePost = Post::find($sourcePostId);
        if (!$sourcePost) {
            return $this->jsonResponse($response, ['error' => 'Source post not found'], 404);
        }

        // Validation
        $content = $data['content'] ?? '';
        if (empty($content) || mb_strlen($content) > 150) {
            return $this->jsonResponse($response, [
                'error' => 'Content is required and must be 150 characters or less',
            ], 400);
        }

        // Create quote post
        $quotingPost = Post::create([
            'user_id' => $userId,
            'type' => Post::TYPE_FEED,
            'content_short' => $content,
        ]);

        // Create quote relation
        Quote::create([
            'source_post_id' => $sourcePostId,
            'quoting_post_id' => $quotingPost->id,
        ]);

        $quotingPost->load(['user', 'quotesAsQuoting.sourcePost.user']);

        return $this->jsonResponse($response, $this->formatPost($quotingPost, true), 201);
    }

    private function formatPost(Post $post, bool $includeQuotes = false): array
    {
        // Parse metadata for image URLs
        $metadata = json_decode($post->metadata ?? '{}', true) ?: [];
        $imageUrls = $metadata['image_urls'] ?? [];

        // Check if user is logged in (jwt middleware sets user_id)
        $currentUserId = $GLOBALS['request_user_id'] ?? null;

        $userReactions = [];
        if ($currentUserId) {
            // Optimization: If eager loaded, use it. Otherwise, query (N+1 risk in list if not careful).
            // However, for Simplicity in this PHP setup without full Auth context in Models, we might rely on a tailored query or just simple filtering if 'reactions' are loaded.
            // Since 'reactions' contains ALL reactions, we can filter by user_id in memory if eager loaded.
            if ($post->relationLoaded('reactions')) {
                $userReactions = $post->reactions
                    ->where('user_id', $currentUserId)
                    ->pluck('emoji')
                    ->unique()
                    ->values()
                    ->toArray();
            }
        }

        $data = [
            'id' => $post->id,
            'type' => $post->type,
            'content' => $post->content_short,
            'image_urls' => $imageUrls,
            'user' => [
                'id' => $post->user->id,
                'username' => $post->user->username,
                'display_name' => $post->user->display_name,
                'avatar_url' => $post->user->avatar_url,
            ],
            'reaction_counts' => $post->reaction_counts,
            'user_reactions' => $userReactions,
            'created_at' => $post->created_at->toISOString(),
            'updated_at' => $post->updated_at->toISOString(),
        ];

        if ($includeQuotes && $post->relationLoaded('quotesAsQuoting')) {
            $data['quoted_posts'] = $post->quotesAsQuoting->map(fn($quote) => [
                'id' => $quote->sourcePost->id,
                'type' => $quote->sourcePost->type,
                'title' => $quote->sourcePost->title, // Added title
                'content' => $quote->sourcePost->content_short ?? mb_substr($quote->sourcePost->content_long ?? '', 0, 150),
                'user' => [
                    'id' => $quote->sourcePost->user->id,
                    'username' => $quote->sourcePost->user->username,
                    'display_name' => $quote->sourcePost->user->display_name,
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
