<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Input Validator
 * 
 * 入力検証とサニタイズ（XSS対策）を提供
 */
class Validator
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * バリデーション結果
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->data;
    }

    /**
     * 必須フィールド
     */
    public function required(string $field, ?string $label = null): self
    {
        $label = $label ?? $field;
        if (!isset($this->data[$field]) || trim((string) $this->data[$field]) === '') {
            $this->errors[$field] = "{$label}は必須です";
        }
        return $this;
    }

    /**
     * メールアドレス形式
     */
    public function email(string $field, ?string $label = null): self
    {
        $label = $label ?? $field;
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label}は有効なメールアドレスではありません";
        }
        return $this;
    }

    /**
     * 最小文字数
     */
    public function min(string $field, int $min, ?string $label = null): self
    {
        $label = $label ?? $field;
        if (isset($this->data[$field]) && mb_strlen($this->data[$field]) < $min) {
            $this->errors[$field] = "{$label}は{$min}文字以上で入力してください";
        }
        return $this;
    }

    /**
     * 最大文字数
     */
    public function max(string $field, int $max, ?string $label = null): self
    {
        $label = $label ?? $field;
        if (isset($this->data[$field]) && mb_strlen($this->data[$field]) > $max) {
            $this->errors[$field] = "{$label}は{$max}文字以内で入力してください";
        }
        return $this;
    }

    /**
     * 英数字とアンダースコアのみ
     */
    public function alphanumeric(string $field, ?string $label = null): self
    {
        $label = $label ?? $field;
        if (isset($this->data[$field]) && !preg_match('/^[a-zA-Z0-9_]+$/', $this->data[$field])) {
            $this->errors[$field] = "{$label}は英数字とアンダースコアのみ使用できます";
        }
        return $this;
    }

    /**
     * XSSサニタイズ - HTMLエンティティをエスケープ
     */
    public function sanitize(string $field): self
    {
        if (isset($this->data[$field])) {
            $this->data[$field] = self::clean($this->data[$field]);
        }
        return $this;
    }

    /**
     * 静的サニタイズメソッド
     */
    public static function clean(string $value): string
    {
        // HTMLタグを除去
        $value = strip_tags($value);
        // HTMLエンティティをエスケープ
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // 危険なプロトコルを除去
        $value = preg_replace('/javascript:/i', '', $value);
        $value = preg_replace('/vbscript:/i', '', $value);
        $value = preg_replace('/data:/i', '', $value);
        return $value;
    }

    /**
     * 配列全体をサニタイズ
     */
    public static function cleanArray(array $data): array
    {
        $cleaned = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $cleaned[$key] = self::clean($value);
            } elseif (is_array($value)) {
                $cleaned[$key] = self::cleanArray($value);
            } else {
                $cleaned[$key] = $value;
            }
        }
        return $cleaned;
    }

    /**
     * 整数値のみ
     */
    public function integer(string $field, ?string $label = null): self
    {
        $label = $label ?? $field;
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = "{$label}は数値で入力してください";
        }
        return $this;
    }

    /**
     * 列挙値チェック
     */
    public function in(string $field, array $allowed, ?string $label = null): self
    {
        $label = $label ?? $field;
        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowed, true)) {
            $allowedStr = implode(', ', $allowed);
            $this->errors[$field] = "{$label}は次のいずれかである必要があります: {$allowedStr}";
        }
        return $this;
    }
}
