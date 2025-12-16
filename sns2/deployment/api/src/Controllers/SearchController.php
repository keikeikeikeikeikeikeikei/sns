<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Helpers\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Search', description: '検索機能')]
class SearchController
{
    #[OA\Get(
        path: '/search',
        summary: '投稿とユーザーを検索',
        tags: ['Search'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string', minLength: 1)),
            new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['all', 'posts', 'users'], default: 'all')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: '検索結果'),
            new OA\Response(response: 400, description: 'クエリが必要'),
        ]
    )]
    public function search(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query = trim($params['q'] ?? '');
        $type = $params['type'] ?? 'all';
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($params['per_page'] ?? 20)));

        // Validation
        if (empty($query)) {
            return $this->jsonResponse($response, [
                'error' => '検索キーワードを入力してください'
            ], 400);
        }

        // Sanitize search query
        $query = Validator::clean($query);

        // Escape LIKE wildcards to prevent SQL injection
        $escapedQuery = str_replace(['%', '_'], ['\\%', '\\_'], $query);

        $result = [
            'query' => $query,
            'type' => $type,
        ];

        // Search posts
        if ($type === 'all' || $type === 'posts') {
            $postsQuery = Post::with(['user'])
                ->where(function ($q) use ($escapedQuery) {
                    $q->where('content_short', 'LIKE', "%{$escapedQuery}%")
                        ->orWhere('content_long', 'LIKE', "%{$escapedQuery}%")
                        ->orWhere('title', 'LIKE', "%{$escapedQuery}%");
                })
                ->latest();

            $totalPosts = $postsQuery->count();
            $posts = $postsQuery
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            $result['posts'] = [
                'data' => $posts->map(fn($post) => $this->formatPost($post)),
                'meta' => [
                    'total' => $totalPosts,
                    'current_page' => $page,
                    'last_page' => (int) ceil($totalPosts / $perPage),
                ],
            ];
        }

        // Search users
        if ($type === 'all' || $type === 'users') {
            $usersQuery = User::where(function ($q) use ($escapedQuery) {
                $q->where('username', 'LIKE', "%{$escapedQuery}%")
                    ->orWhere('display_name', 'LIKE', "%{$escapedQuery}%")
                    ->orWhere('bio', 'LIKE', "%{$escapedQuery}%");
            });

            $totalUsers = $usersQuery->count();
            $users = $usersQuery
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            $result['users'] = [
                'data' => $users->map(fn($user) => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'display_name' => $user->display_name,
                    'bio' => $user->bio,
                    'avatar_url' => $user->avatar_url,
                ]),
                'meta' => [
                    'total' => $totalUsers,
                    'current_page' => $page,
                    'last_page' => (int) ceil($totalUsers / $perPage),
                ],
            ];
        }

        return $this->jsonResponse($response, $result);
    }

    private function formatPost(Post $post): array
    {
        $content = $post->content_short ?? mb_substr($post->content_long ?? '', 0, 200);

        return [
            'id' => $post->id,
            'type' => $post->type,
            'title' => $post->title,
            'content' => $content,
            'user' => [
                'id' => $post->user->id,
                'username' => $post->user->username,
                'display_name' => $post->user->display_name,
                'avatar_url' => $post->user->avatar_url,
            ],
            'created_at' => $post->created_at->toISOString(),
        ];
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
