# SNS Project Repository

> **Warning**
> このプロジェクトは、技術習得および実験を目的として作成されたプロトタイプです。
> セキュリティ対策やパフォーマンスチューニングは完全ではない可能性があるため、**コードを修正せずにそのまま本番環境で使用することは推奨されません**。

このディレクトリは、独自SNSアプリケーションの開発アーカイブです。
開発フェーズや目的に応じて、以下の2つの主要なプロジェクトディレクトリが含まれています。

## ディレクトリ構成

### 1. `sns/` (Development Sandbox)
開発段階の検証用コードベースです。機能の実装実験や、セキュリティ・APIの挙動を確認するためのスクリプトが多く含まれています。

- **主な特徴**:
    - API検証用スクリプト (`verify_all.php`, `verify_security.php` 等) がルートに含まれています。
    - テストデータやデバッグ用の設定が残されています。
    - 機能ごとの単体動作確認や、試行錯誤のプロセスが含まれています。

### 2. `sns2/` (Structure Refined)
ディレクトリ構造を見直し、整理された本番デプロイメントを意識したバージョンです。

- **主な特徴**:
    - バックエンドとフロントエンドが整理され、標準的な構成になっています。
    - デプロイ用スクリプト (`deployment/`) や、セットアップガイド (`STARTUP_GUIDE.md`) が整備されています。
    - ライセンスファイルが含まれ、より配布・運用に適した形になっています。

### 3. `laravel-auth-system/` (Legacy V1 with Auth)
Laravel 11を使用した、認証機能付きの標準的なWebアプリケーション構成です。
メール認証、ログイン、パスワードリセットなどの基本機能が実装されています。

- **主な特徴**:
    - **Framework**: Laravel 11 (PHP 8.2+)
    - **Frontend**: Blade Templates + TailwindCSS (via CDN)
    - **Database**: SQLite (configured for portability), MySQL compatible
    - **Features**:
        - 完全な認証システム (Breezeベース)
        - ユーザーダッシュボード
        - プロフィール編集機能
- **用途**:
    - Laravelベースでの再構築や、認証ロジックの参考用。
    - すぐに立ち上がるローカルテスト環境として利用可能。

## プロジェクト共通仕様

両プロジェクトは、以下の仕様に基づいたSNSアプリケーションの実装です。

### 提供機能
1. **つぶやき (Microblog)**
   - 150文字以内の短文投稿
   - 画像添付、引用機能

2. **ブログ (Long-form Blog)**
   - 長文記事の作成・公開
   - 文字数制限緩和版の投稿機能

3. **Q&A (Question & Answer)**
   - 質問と回答の投稿
   - ベストアンサー選定機能（解決済みステータス管理）

### 共通機能
- **引用システム (Polymorphic Quoting)**: つぶやき、ブログ、Q&Aの各コンテンツを相互に引用可能。
- **リアクション**: 一般的な「いいね」ではなく、任意のUnicode絵文字を使用したリアクションが可能。

## 技術スタック

各プロジェクトごとの採用技術は以下の通りです。

### 1. `sns/` の技術スタック
開発・検証用の簡易構成です。
*参照: `sns/TechnologyStack.md`*

- **Backend**:
    - 言語: PHP 8.3
    - フレームワーク: Slim 4.14
    - ORM: Illuminate/Eloquent (Laravel) 10.0
    - その他: firebase/php-jwt, ramsey/uuid, zircote/swagger-php, symfony/validator
- **Frontend**:
    - フレームワーク: Vue 3 (Composition API)
    - 言語: TypeScript
    - ビルドツール: Vite 6
    - スタイリング: Tailwind CSS + PrimeVue
    - APIクライアント: Orval (自動生成)
    - 状態管理: TanStack Query

### 2. `sns2/` の技術スタック
本番運用を見据え、ログ管理や環境変数管理、テストツールなどの構成要素が追加されています。
*参照: `sns2/TechnologyStack.md`*

- **Backend**:
    - 言語: PHP 8.3
    - フレームワーク: Slim 4.14
    - ORM: Illuminate/Eloquent (Laravel) 10.0
    - 認証: firebase/php-jwt
    - **追加ライブラリ**: vlucas/phpdotenv (環境変数), monolog/monolog (ログ)
- **Frontend**:
    - フレームワーク: Vue 3 (Composition API)
    - 言語: TypeScript
    - ビルドツール: Vite 6
    - スタイリング: Tailwind CSS + PrimeVue
    - APIクライアント: Orval (自動生成), **Axios** (HTTPクライアント)
    - 状態管理: TanStack Query
- **Development & Testing**:
    - Backend Test: PHPUnit
    - Frontend Test: Vitest
    - Tools: Redocly CLI
- **Database**:
    - Default: SQLite (開発・簡易実行用)
    - Production Ready: MariaDB 10.5+ / MySQL 8.0+
        - *Note*: `config/database.php` に接続切替ロジック実装済み。`deployment/schema.sql` にMySQL用スキーマあり。現状のデフォルト動作はSQLite。
