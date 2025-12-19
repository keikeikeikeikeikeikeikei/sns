# Laravel Project (V1 Login System)

このプロジェクトは、Laravel 11 をベースにしたメール認証およびSSO機能を持つWebアプリケーションです。

## 前提条件

- PHP 8.2 以上
- SQLite (または MySQL/MariaDB)
- Composer

## セットアップ手順

このリポジトリをダウンロード（クローン）した後の手順です。

### 1. 依存ライブラリのインストール
`laravel` ディレクトリに移動し、Composerでライブラリをインストールします。

```bash
cd laravel
composer install
chmod -R 777 storage bootstrap/cache
```

### 2. 環境設定ファイルの作成
配布用の `.env.example` をコピーして `.env` を作成します。

```bash
cp .env.example .env
```

### 3. アプリケーションキーの生成
Laravelの暗号化キーを生成します。

```bash
php artisan key:generate
```

### 4. データベースの準備
デフォルトでは SQLite を使用するように設定されています。
（マイグレーションを実行してテーブルを作成します）

```bash
# データベースファイル作成（ない場合）
touch database/database.sqlite

# マイグレーション実行
php artisan migrate
```

### 5. サーバーの起動
プロジェクトのルート（`laravel` の一つ上の階層）でサーバーを起動します。

```bash
cd ..
php -S localhost:8000 -t public_html
```

ブラウザで `http://localhost:8000` にアクセスしてください。

### テスト用アカウント
あらかじめ以下のテスト用データが含まれています（`database/database.sqlite`）。

- **メールアドレス**: `test@example.com`
- **パスワード**: `test@example.com`


## ディレクトリ構成
- `laravel/` : バックエンドのソースコード (Laravel 11)
- `public_html/` : 公開ディレクトリ (エントリーポイント `index.php` や静的ファイル)

## 注意事項
- このプロジェクトは `public_html/index.php` をエントリーポイントとして、`laravel/` ディレクトリ内のコアを呼び出す構成になっています。
- デプロイ時は `public_html` をドキュメントルートに設定してください。
