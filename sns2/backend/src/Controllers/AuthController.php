<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Helpers\Validator;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

#[OA\Info(title: "SNS_2A API", version: "1.0.0")]
#[OA\Server(url: "/api", description: "API Server")]
class AuthController
{
    #[OA\Post(
        path: '/auth/register',
        summary: 'ユーザー登録',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', maxLength: 50),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8),
                    new OA\Property(property: 'display_name', type: 'string', maxLength: 255),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '登録成功'),
            new OA\Response(response: 400, description: 'バリデーションエラー'),
            new OA\Response(response: 409, description: 'ユーザー名またはメールが既に存在'),
        ]
    )]
    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody() ?? [];

        // Validation with sanitization
        $validator = new Validator($data);
        $validator
            ->required('username', 'ユーザー名')
            ->alphanumeric('username', 'ユーザー名')
            ->min('username', 3, 'ユーザー名')
            ->max('username', 50, 'ユーザー名')
            ->sanitize('username')
            ->required('email', 'メールアドレス')
            ->email('email', 'メールアドレス')
            ->required('password', 'パスワード')
            ->min('password', 8, 'パスワード')
            ->max('password', 128, 'パスワード') // Prevent DoS via bcrypt
            ->sanitize('display_name');

        if ($validator->fails()) {
            return $this->jsonResponse($response, ['errors' => $validator->errors()], 400);
        }

        $validated = $validator->validated();

        // Check existing user
        if (User::where('username', $validated['username'])->exists()) {
            return $this->jsonResponse($response, ['error' => 'このユーザー名は既に使用されています'], 409);
        }
        if (User::where('email', $validated['email'])->exists()) {
            return $this->jsonResponse($response, ['error' => 'このメールアドレスは既に使用されています'], 409);
        }

        // Create user with sanitized data
        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'display_name' => $validated['display_name'] ?? $validated['username'],
        ]);

        $token = $this->generateToken($user);

        return $this->jsonResponse($response, [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'display_name' => $user->display_name,
            ],
            'token' => $token,
        ], 201);
    }

    #[OA\Post(
        path: '/auth/login',
        summary: 'ログイン',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'ログイン成功'),
            new OA\Response(response: 401, description: '認証失敗'),
        ]
    )]
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $user = User::where('email', $data['email'] ?? '')->first();

        if (!$user || !password_verify($data['password'] ?? '', $user->password_hash)) {
            return $this->jsonResponse($response, ['error' => 'Invalid credentials'], 401);
        }

        $token = $this->generateToken($user);

        return $this->jsonResponse($response, [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'display_name' => $user->display_name,
            ],
            'token' => $token,
        ]);
    }

    #[OA\Get(
        path: '/me',
        summary: '現在のユーザー情報取得',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: '成功'),
            new OA\Response(response: 401, description: '未認証'),
        ]
    )]
    public function me(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = User::find($userId);

        if (!$user) {
            return $this->jsonResponse($response, ['error' => 'User not found'], 404);
        }

        return $this->jsonResponse($response, [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'display_name' => $user->display_name,
            'bio' => $user->bio,
            'avatar_url' => $user->avatar_url,
            'created_at' => $user->created_at->toISOString(),
        ]);
    }

    private function generateToken(User $user): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? 'default-secret';
        $issuer = $_ENV['JWT_ISSUER'] ?? 'sns2-api';
        $expiry = (int) ($_ENV['JWT_EXPIRY'] ?? 3600);

        $payload = [
            'iss' => $issuer,
            'sub' => $user->id,
            'username' => $user->username,
            'iat' => time(),
            'exp' => time() + $expiry,
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
