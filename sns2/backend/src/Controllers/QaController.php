<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'QA', description: 'Q&A（質問と回答）')]
class QaController
{
    #[OA\Get(
        path: '/qa',
        summary: 'Q&A一覧取得',
        tags: ['QA'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['open', 'resolved'])),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
        ]
    )]
    public function index(Request $request, Response $response): Response
    {
        $GLOBALS['request_user_id'] = $request->getAttribute('user_id');
        $params = $request->getQueryParams();
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($params['per_page'] ?? 20)));

        $query = Post::qa()->latest()->with(['user', 'reactions', 'answers', 'quotesAsQuoting.sourcePost.user']);

        if (!empty($params['status'])) {
            $query->where('qa_status', $params['status']);
        }

        $total = (clone $query)->count();
        $questions = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return $this->jsonResponse($response, [
            'data' => $questions->map(fn($q) => $this->formatQuestion($q)),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    #[OA\Post(
        path: '/qa',
        summary: '質問投稿',
        tags: ['QA'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255),
                    new OA\Property(property: 'content', type: 'string'),
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
        }

        if (!empty($errors)) {
            return $this->jsonResponse($response, ['errors' => $errors], 400);
        }

        $question = Post::create([
            'user_id' => $userId,
            'type' => Post::TYPE_QA,
            'title' => $data['title'],
            'content_long' => $data['content'],
            'qa_status' => Post::QA_STATUS_OPEN,
        ]);

        $question->load('user');

        return $this->jsonResponse($response, $this->formatQuestion($question), 201);
    }

    #[OA\Get(
        path: '/qa/{id}',
        summary: 'Q&A詳細取得（回答含む）',
        tags: ['QA'],
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
        $GLOBALS['request_user_id'] = $request->getAttribute('user_id');
        $qaId = (int) $args['id'];
        $question = Post::qa()
            ->with(['user', 'reactions', 'answers.user', 'answers.reactions', 'bestAnswer', 'quotesAsQuoting.sourcePost.user'])
            ->find($qaId);

        if (!$question) {
            return $this->jsonResponse($response, ['error' => 'Question not found'], 404);
        }

        return $this->jsonResponse($response, $this->formatQuestion($question, true));
    }

    #[OA\Put(
        path: '/qa/{id}',
        summary: '質問更新',
        tags: ['QA'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255),
                    new OA\Property(property: 'content', type: 'string'),
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
        $question = Post::qa()->find($args['id']);

        if (!$question) {
            return $this->jsonResponse($response, ['error' => 'Question not found'], 404);
        }

        if ($question->user_id !== $userId) {
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
            $updateData['content_long'] = $data['content'];
        }

        if (!empty($updateData)) {
            $question->update($updateData);
        }

        $question->load('user');

        return $this->jsonResponse($response, $this->formatQuestion($question));
    }

    #[OA\Delete(
        path: '/qa/{id}',
        summary: '質問削除',
        tags: ['QA'],
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
        $question = Post::qa()->find($args['id']);

        if (!$question) {
            return $this->jsonResponse($response, ['error' => 'Question not found'], 404);
        }

        if ($question->user_id !== $userId) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        // Delete answers too
        $question->answers()->delete();
        $question->delete();

        return $response->withStatus(204);
    }

    #[OA\Post(
        path: '/qa/{id}/answers',
        summary: '回答投稿',
        tags: ['QA'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '回答投稿成功'),
            new OA\Response(response: 404, description: '質問が見つからない'),
        ]
    )]
    public function storeAnswer(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $questionId = (int) $args['id'];
        $data = $request->getParsedBody();

        $question = Post::qa()->find($questionId);
        if (!$question) {
            return $this->jsonResponse($response, ['error' => 'Question not found'], 404);
        }

        if (empty($data['content'])) {
            return $this->jsonResponse($response, ['error' => 'Content is required'], 400);
        }

        $answer = Post::create([
            'user_id' => $userId,
            'type' => Post::TYPE_QA,
            'content_long' => $data['content'],
            'parent_post_id' => $questionId,
        ]);

        $answer->load('user');

        return $this->jsonResponse($response, $this->formatAnswer($answer), 201);
    }

    #[OA\Put(
        path: '/qa/{id}/best-answer',
        summary: 'ベストアンサー選択',
        tags: ['QA'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['answer_id'],
                properties: [
                    new OA\Property(property: 'answer_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '選択成功'),
            new OA\Response(response: 403, description: '権限なし（質問者のみ選択可能）'),
            new OA\Response(response: 404, description: '質問または回答が見つからない'),
        ]
    )]
    public function setBestAnswer(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $question = Post::qa()->find($args['id']);

        if (!$question) {
            return $this->jsonResponse($response, ['error' => 'Question not found'], 404);
        }

        // Only question author can select best answer
        if ($question->user_id !== $userId) {
            return $this->jsonResponse($response, ['error' => 'Only question author can select best answer'], 403);
        }

        $data = $request->getParsedBody();
        $answerId = $data['answer_id'] ?? null;

        // Verify answer belongs to this question
        $answer = $question->answers()->find($answerId);
        if (!$answer) {
            return $this->jsonResponse($response, ['error' => 'Answer not found for this question'], 404);
        }

        $question->update([
            'best_answer_id' => $answerId,
            'qa_status' => Post::QA_STATUS_RESOLVED,
        ]);

        $question->load(['user', 'answers.user', 'bestAnswer']);

        return $this->jsonResponse($response, $this->formatQuestion($question));
    }

    private function formatQuestion(Post $question, bool $includeAnswers = false): array
    {
        $currentUserId = $GLOBALS['request_user_id'] ?? null;
        $userReactions = [];
        if ($currentUserId && $question->relationLoaded('reactions')) {
            $userReactions = $question->reactions
                ->where('user_id', $currentUserId)
                ->pluck('emoji')
                ->unique()
                ->values()
                ->toArray();
        }

        $data = [
            'id' => $question->id,
            'type' => $question->type,
            'status' => $question->qa_status,
            'title' => $question->title,
            'content' => $question->content_long,
            'user' => [
                'id' => $question->user->id,
                'username' => $question->user->username,
                'display_name' => $question->user->display_name,
                'avatar_url' => $question->user->avatar_url,
            ],
            'reaction_counts' => $question->reaction_counts,
            'user_reactions' => $userReactions,
            'answer_count' => $question->answers->count(),
            'best_answer_id' => $question->best_answer_id,
            'created_at' => $question->created_at->toISOString(),
            'updated_at' => $question->updated_at->toISOString(),
        ];

        if ($includeAnswers) {
            $data['answers'] = $question->answers->map(fn($a) => $this->formatAnswer($a));
        }

        if ($question->relationLoaded('quotesAsQuoting')) {
            $data['quoted_posts'] = $question->quotesAsQuoting->map(fn($quote) => [
                'id' => $quote->sourcePost->id,
                'type' => $quote->sourcePost->type,
                'title' => $quote->sourcePost->title,
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

    private function formatAnswer(Post $answer): array
    {
        $currentUserId = $GLOBALS['request_user_id'] ?? null;
        $userReactions = [];
        if ($currentUserId && $answer->relationLoaded('reactions')) {
            $userReactions = $answer->reactions
                ->where('user_id', $currentUserId)
                ->pluck('emoji')
                ->unique()
                ->values()
                ->toArray();
        }

        return [
            'id' => $answer->id,
            'content' => $answer->content_long,
            'is_best_answer' => $answer->parentPost?->best_answer_id === $answer->id,
            'user' => [
                'id' => $answer->user->id,
                'username' => $answer->user->username,
                'display_name' => $answer->user->display_name,
                'avatar_url' => $answer->user->avatar_url,
            ],
            'reaction_counts' => $answer->reaction_counts,
            'user_reactions' => $userReactions,
            'created_at' => $answer->created_at->toISOString(),
            'updated_at' => $answer->updated_at->toISOString(),
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
