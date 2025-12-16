<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Upload', description: '画像アップロード')]
class UploadController
{
    private string $uploadDir;
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];
    private int $maxFileSize = 5 * 1024 * 1024; // 5MB per file
    private int $maxUserStorage = 50 * 1024 * 1024; // 50MB per user
    private int $maxTotalStorage = 1024 * 1024 * 1024; // 1GB total

    public function __construct()
    {
        $this->uploadDir = __DIR__ . '/../../public/uploads';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    #[OA\Post(
        path: '/upload',
        summary: '画像をアップロード',
        tags: ['Upload'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'image', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'アップロード成功'),
            new OA\Response(response: 400, description: 'バリデーションエラー'),
        ]
    )]
    public function upload(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $uploadedFiles = $request->getUploadedFiles();

        if (empty($uploadedFiles['image'])) {
            return $this->jsonResponse($response, [
                'error' => '画像ファイルが必要です'
            ], 400);
        }

        /** @var UploadedFileInterface $uploadedFile */
        $uploadedFile = $uploadedFiles['image'];

        // Check for upload errors
        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return $this->jsonResponse($response, [
                'error' => 'アップロード中にエラーが発生しました'
            ], 400);
        }

        // Validate file size
        $fileSize = $uploadedFile->getSize();
        if ($fileSize > $this->maxFileSize) {
            return $this->jsonResponse($response, [
                'error' => 'ファイルサイズは5MB以内にしてください'
            ], 400);
        }

        // Check user storage quota
        $userUsedStorage = $this->getUserStorageUsage($userId);
        if ($userUsedStorage + $fileSize > $this->maxUserStorage) {
            $remainingMB = round(($this->maxUserStorage - $userUsedStorage) / 1024 / 1024, 1);
            return $this->jsonResponse($response, [
                'error' => "ストレージ容量が不足しています（残り: {$remainingMB}MB）"
            ], 400);
        }

        // Check total storage
        $totalUsedStorage = $this->getTotalStorageUsage();
        if ($totalUsedStorage + $fileSize > $this->maxTotalStorage) {
            return $this->jsonResponse($response, [
                'error' => 'サーバーのストレージ容量が不足しています'
            ], 400);
        }

        // Validate MIME type from client
        $mimeType = $uploadedFile->getClientMediaType();
        if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            return $this->jsonResponse($response, [
                'error' => '対応している形式: JPEG, PNG, GIF, WebP'
            ], 400);
        }

        // Validate actual file content (magic bytes) - security against MIME spoofing
        $stream = $uploadedFile->getStream();
        $stream->rewind();
        $header = $stream->read(12);
        $stream->rewind();

        $actualMimeType = $this->detectMimeFromMagicBytes($header);
        if ($actualMimeType === null || !in_array($actualMimeType, $this->allowedMimeTypes, true)) {
            return $this->jsonResponse($response, [
                'error' => 'ファイル形式が不正です'
            ], 400);
        }

        // Generate unique filename
        $extension = $this->getExtension($actualMimeType);
        $filename = $this->generateFilename($extension);
        $filepath = $this->uploadDir . '/' . $filename;

        // Move uploaded file
        $uploadedFile->moveTo($filepath);

        // Strip EXIF/metadata (GPS location, camera info, etc.) for privacy
        $this->stripMetadata($filepath, $actualMimeType);

        // Update user's storage usage (use actual file size after re-encoding)
        $actualFileSize = filesize($filepath);
        $this->updateUserStorageUsage($userId, $actualFileSize);

        // Return URL
        $baseUrl = $this->getBaseUrl($request);
        $imageUrl = $baseUrl . '/uploads/' . $filename;

        return $this->jsonResponse($response, [
            'url' => $imageUrl,
            'filename' => $filename,
        ]);
    }

    #[OA\Post(
        path: '/upload/multiple',
        summary: '複数画像をアップロード',
        tags: ['Upload'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'images[]', type: 'array', items: new OA\Items(type: 'string', format: 'binary')),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'アップロード成功'),
            new OA\Response(response: 400, description: 'バリデーションエラー'),
        ]
    )]
    public function uploadMultiple(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $uploadedFiles = $request->getUploadedFiles();
        $images = $uploadedFiles['images'] ?? [];

        if (!is_array($images)) {
            $images = [$images];
        }

        if (empty($images)) {
            return $this->jsonResponse($response, [
                'error' => '画像ファイルが必要です'
            ], 400);
        }

        if (count($images) > 4) {
            return $this->jsonResponse($response, [
                'error' => '一度にアップロードできるのは4枚までです'
            ], 400);
        }

        // Pre-check user storage quota
        $userUsedStorage = $this->getUserStorageUsage($userId);
        $totalUploadSize = array_sum(array_map(fn($f) => $f->getSize(), $images));

        if ($userUsedStorage + $totalUploadSize > $this->maxUserStorage) {
            $remainingMB = round(($this->maxUserStorage - $userUsedStorage) / 1024 / 1024, 1);
            return $this->jsonResponse($response, [
                'error' => "ストレージ容量が不足しています（残り: {$remainingMB}MB）"
            ], 400);
        }

        $urls = [];
        $baseUrl = $this->getBaseUrl($request);
        $totalAddedBytes = 0;

        foreach ($images as $uploadedFile) {
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                continue;
            }

            if ($uploadedFile->getSize() > $this->maxFileSize) {
                continue;
            }

            $mimeType = $uploadedFile->getClientMediaType();
            if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
                continue;
            }

            // Validate magic bytes
            $stream = $uploadedFile->getStream();
            $stream->rewind();
            $header = $stream->read(12);
            $stream->rewind();

            $actualMimeType = $this->detectMimeFromMagicBytes($header);
            if ($actualMimeType === null || !in_array($actualMimeType, $this->allowedMimeTypes, true)) {
                continue;
            }

            $extension = $this->getExtension($actualMimeType);
            $filename = $this->generateFilename($extension);
            $filepath = $this->uploadDir . '/' . $filename;

            $uploadedFile->moveTo($filepath);

            // Strip EXIF/metadata for privacy
            $this->stripMetadata($filepath, $actualMimeType);

            // Track actual file size after re-encoding
            $totalAddedBytes += filesize($filepath);

            $urls[] = $baseUrl . '/uploads/' . $filename;
        }

        // Update user's storage usage
        if ($totalAddedBytes > 0) {
            $this->updateUserStorageUsage($userId, $totalAddedBytes);
        }

        return $this->jsonResponse($response, [
            'urls' => $urls,
            'count' => count($urls),
        ]);
    }

    private function generateFilename(string $extension): string
    {
        return sprintf(
            '%s_%s.%s',
            date('Ymd_His'),
            bin2hex(random_bytes(8)),
            $extension
        );
    }

    /**
     * Detect MIME type from file magic bytes (file signature)
     */
    private function detectMimeFromMagicBytes(string $header): ?string
    {
        // JPEG: FF D8 FF
        if (strlen($header) >= 3 && substr($header, 0, 3) === "\xFF\xD8\xFF") {
            return 'image/jpeg';
        }

        // PNG: 89 50 4E 47 0D 0A 1A 0A
        if (strlen($header) >= 8 && substr($header, 0, 8) === "\x89PNG\r\n\x1A\n") {
            return 'image/png';
        }

        // GIF: GIF87a or GIF89a
        if (strlen($header) >= 6 && (substr($header, 0, 6) === "GIF87a" || substr($header, 0, 6) === "GIF89a")) {
            return 'image/gif';
        }

        // WebP: RIFF....WEBP
        if (strlen($header) >= 12 && substr($header, 0, 4) === "RIFF" && substr($header, 8, 4) === "WEBP") {
            return 'image/webp';
        }

        return null;
    }

    private function getExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }

    private function getBaseUrl(Request $request): string
    {
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $port = $uri->getPort();

        $baseUrl = "{$scheme}://{$host}";
        if ($port && $port !== 80 && $port !== 443) {
            $baseUrl .= ":{$port}";
        }

        return $baseUrl;
    }

    /**
     * Get user's current storage usage from metadata field
     */
    private function getUserStorageUsage(int $userId): int
    {
        $user = User::find($userId);
        if (!$user) {
            return 0;
        }

        $metadata = json_decode($user->metadata ?? '{}', true) ?: [];
        return (int) ($metadata['storage_used'] ?? 0);
    }

    /**
     * Update user's storage usage
     */
    private function updateUserStorageUsage(int $userId, int $addedBytes): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $metadata = json_decode($user->metadata ?? '{}', true) ?: [];
        $metadata['storage_used'] = ($metadata['storage_used'] ?? 0) + $addedBytes;
        $user->metadata = json_encode($metadata);
        $user->save();
    }

    /**
     * Get total storage usage by scanning uploads directory
     */
    private function getTotalStorageUsage(): int
    {
        $total = 0;
        $files = glob($this->uploadDir . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                $total += filesize($file);
            }
        }

        return $total;
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Strip EXIF/metadata from image (GPS location, camera info, etc.) for privacy
     * Re-encodes the image using GD to remove all metadata
     */
    private function stripMetadata(string $filepath, string $mimeType): void
    {
        // Check if GD is available
        if (!extension_loaded('gd')) {
            return; // Skip if GD not available
        }

        try {
            // Load image based on type
            $image = match ($mimeType) {
                'image/jpeg' => imagecreatefromjpeg($filepath),
                'image/png' => imagecreatefrompng($filepath),
                'image/gif' => imagecreatefromgif($filepath),
                'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($filepath) : null,
                default => null,
            };

            if ($image === null || $image === false) {
                return;
            }

            // For PNG, preserve transparency
            if ($mimeType === 'image/png') {
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }

            // Re-encode to strip metadata
            match ($mimeType) {
                'image/jpeg' => imagejpeg($image, $filepath, 90), // 90% quality
                'image/png' => imagepng($image, $filepath, 6), // compression level 6
                'image/gif' => imagegif($image, $filepath),
                'image/webp' => function_exists('imagewebp') ? imagewebp($image, $filepath, 90) : null,
                default => null,
            };

            imagedestroy($image);
        } catch (\Throwable $e) {
            // Silently fail - original file is still valid
        }
    }
}
