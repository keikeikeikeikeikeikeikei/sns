# SNS Application

> **⚠️ 注意 / WARNING**
>
> このプロジェクトは個人学習および実験目的で作成されたプロトタイプ（おもちゃ）です。
> セキュリティ対策（CSRF対策、高度な認証管理など）やパフォーマンス最適化は本番運用を想定していません。
> **絶対にそのまま本番環境（公開サーバー）で使用しないでください。**

このプロジェクトは、マイクロブログ、Q&A、ブログ機能を統合した実験的なSNSアプリケーションです。

## 機能概要

*   **タイムライン (Feed)**: 150文字以内の短文投稿機能。画像添付や他投稿の引用が可能。
*   **Q&A**: 質問の投稿と回答、ベストアンサーの選定機能。
*   **ブログ**: 1万文字までの長文記事投稿機能。
*   **リアクション**: 任意のUnicode絵文字を使用したリアクション機能。

## 技術スタック

### Frontend
*   **Framework**: React 18
*   **Build Tool**: Vite
*   **Language**: TypeScript
*   **Routing**: React Router
*   **Styling**: Vanilla CSS
*   **Others**: `emoji-picker-react`

### Backend
*   **Language**: PHP (Native)
*   **Database**: SQLite (`sns_debug.db`)
*   **API**: RESTful API design

## セットアップと起動方法

### 前提条件
*   PHP 8.0以上 (要SQLite拡張)
*   Node.js (LTS推奨)
*   npm

### 1. バックエンドの起動

SQLiteを使用しているため、別途データベースサーバーのインストールは不要です。

```bash
# データベースの初期化（初回のみ）
# ルートディレクトリで実行してください
php init_db.php

# 開発サーバーの起動
cd backend/public
php -S localhost:8000
```
APIサーバーは `http://localhost:8000` で起動します。

### 2. フロントエンドの起動

新しいターミナルを開いて実行してください。

```bash
cd frontend

# 依存関係のインストール
npm install

# 開発サーバーの起動
npm run dev
```
ブラウザでローカルサーバーのアドレス（通常は `http://localhost:5173`）を開いてください。

## ディレクトリ構成

*   `backend/`: APIサーバーのソースコード
    *   `src/`: PHPクラスファイル
    *   `public/`: エントリーポイント
*   `frontend/`: フロントエンドアプリケーションのソースコード
*   `init_db.php`: データベース初期化スクリプト
*   `*.php` (ルート): その他は開発中の動作確認・検証用スクリプトです。
