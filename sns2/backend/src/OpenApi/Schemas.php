<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'JWT認証トークン'
)]
#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'username', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'display_name', type: 'string'),
        new OA\Property(property: 'bio', type: 'string', nullable: true),
        new OA\Property(property: 'avatar_url', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'UserSummary',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'username', type: 'string'),
        new OA\Property(property: 'display_name', type: 'string'),
        new OA\Property(property: 'avatar_url', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Feed',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'type', type: 'string', enum: ['feed']),
        new OA\Property(property: 'content', type: 'string', maxLength: 150),
        new OA\Property(property: 'user', ref: '#/components/schemas/UserSummary'),
        new OA\Property(property: 'reaction_counts', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'integer')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Question',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'type', type: 'string', enum: ['qa']),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'content', type: 'string'),
        new OA\Property(property: 'status', type: 'string', enum: ['open', 'resolved']),
        new OA\Property(property: 'best_answer_id', type: 'integer', nullable: true),
        new OA\Property(property: 'user', ref: '#/components/schemas/UserSummary'),
        new OA\Property(property: 'reaction_counts', type: 'object'),
        new OA\Property(property: 'answer_count', type: 'integer'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Answer',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'content', type: 'string'),
        new OA\Property(property: 'is_best_answer', type: 'boolean'),
        new OA\Property(property: 'user', ref: '#/components/schemas/UserSummary'),
        new OA\Property(property: 'reaction_counts', type: 'object'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Blog',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'type', type: 'string', enum: ['blog']),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'content', type: 'string', maxLength: 10000),
        new OA\Property(property: 'user', ref: '#/components/schemas/UserSummary'),
        new OA\Property(property: 'reaction_counts', type: 'object'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer'),
        new OA\Property(property: 'per_page', type: 'integer'),
        new OA\Property(property: 'total', type: 'integer'),
        new OA\Property(property: 'last_page', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'Error',
    properties: [
        new OA\Property(property: 'error', type: 'string'),
    ]
)]
class Schemas
{
    // This class only holds OpenAPI schema definitions
}
