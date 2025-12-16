<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;
use App\Models\Reaction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Reaction', description: '絵文字リアクション')]
class ReactionController
{
    #[OA\Get(
        path: '/posts/{id}/reactions',
        summary: '投稿のリアクション一覧取得',
        tags: ['Reaction'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
            new OA\Response(response: 404, description: '投稿が見つからない'),
        ]
    )]
    public function index(Request $request, Response $response, array $args): Response
    {
        $postId = (int) $args['id'];
        $post = Post::find($postId);

        if (!$post) {
            return $this->jsonResponse($response, ['error' => 'Post not found'], 404);
        }

        // Get reaction counts grouped by emoji
        $reactionCounts = Reaction::where('post_id', $postId)
            ->selectRaw('emoji, COUNT(*) as count')
            ->groupBy('emoji')
            ->get()
            ->map(fn($r) => [
                'emoji' => $r->emoji,
                'count' => $r->count,
            ]);

        // Get current user's reactions
        $userId = $request->getAttribute('user_id');
        $userReactions = Reaction::where('post_id', $postId)
            ->where('user_id', $userId)
            ->pluck('emoji')
            ->toArray();

        return $this->jsonResponse($response, [
            'reactions' => $reactionCounts,
            'user_reactions' => $userReactions,
        ]);
    }

    #[OA\Post(
        path: '/posts/{id}/reactions',
        summary: 'リアクション追加',
        description: '同一ユーザー・同一投稿・同一絵文字は1回のみ。異なる絵文字は複数追加可能。',
        tags: ['Reaction'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['emoji'],
                properties: [
                    new OA\Property(property: 'emoji', type: 'string', description: 'Unicode絵文字', example: '👍'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'リアクション追加成功'),
            new OA\Response(response: 400, description: '無効な絵文字'),
            new OA\Response(response: 404, description: '投稿が見つからない'),
            new OA\Response(response: 409, description: '既にリアクション済み'),
        ]
    )]
    public function store(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $postId = (int) $args['id'];
        $data = $request->getParsedBody();

        $post = Post::find($postId);
        if (!$post) {
            return $this->jsonResponse($response, ['error' => 'Post not found'], 404);
        }

        $emoji = $data['emoji'] ?? '';

        // Validate emoji (simple check for Unicode emoji)
        if (empty($emoji) || mb_strlen($emoji) > 32) {
            return $this->jsonResponse($response, ['error' => 'Invalid emoji'], 400);
        }

        // Check if already reacted with same emoji
        $existing = Reaction::where('user_id', $userId)
            ->where('post_id', $postId)
            ->where('emoji', $emoji)
            ->exists();

        if ($existing) {
            return $this->jsonResponse($response, ['error' => 'Already reacted with this emoji'], 409);
        }

        Reaction::create([
            'user_id' => $userId,
            'post_id' => $postId,
            'emoji' => $emoji,
        ]);

        // Return updated reaction counts
        $reactionCounts = Reaction::where('post_id', $postId)
            ->selectRaw('emoji, COUNT(*) as count')
            ->groupBy('emoji')
            ->get()
            ->map(fn($r) => [
                'emoji' => $r->emoji,
                'count' => $r->count,
            ]);

        return $this->jsonResponse($response, [
            'message' => 'Reaction added',
            'reactions' => $reactionCounts,
        ], 201);
    }

    #[OA\Delete(
        path: '/posts/{id}/reactions/{emoji}',
        summary: 'リアクション削除',
        tags: ['Reaction'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'emoji', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'リアクション削除成功'),
            new OA\Response(response: 404, description: 'リアクションが見つからない'),
        ]
    )]
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $postId = (int) $args['id'];
        $emoji = urldecode($args['emoji']);

        $reaction = Reaction::where('user_id', $userId)
            ->where('post_id', $postId)
            ->where('emoji', $emoji)
            ->first();

        if (!$reaction) {
            return $this->jsonResponse($response, ['error' => 'Reaction not found'], 404);
        }

        $reaction->delete();

        // Return updated reaction counts
        $reactionCounts = Reaction::where('post_id', $postId)
            ->selectRaw('emoji, COUNT(*) as count')
            ->groupBy('emoji')
            ->get()
            ->map(fn($r) => [
                'emoji' => $r->emoji,
                'count' => $r->count,
            ]);

        return $this->jsonResponse($response, [
            'message' => 'Reaction removed',
            'reactions' => $reactionCounts,
        ]);
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
