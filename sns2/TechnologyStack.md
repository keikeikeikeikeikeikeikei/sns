# Technology Stack

## Backend
| 項目 | 技術 |
|------|------|
| 言語 | PHP 8.3 |
| フレームワーク | Slim 4.14 |
| ORM | Illuminate/Eloquent (Laravel) 10.0 |
| 認証 | firebase/php-jwt |
| UUID生成 | ramsey/uuid |
| API仕様 | zircote/swagger-php (Code-First OpenAPI) |
| バリデーション | symfony/validator |
| 環境変数 | vlucas/phpdotenv |
| ログ | monolog/monolog |

## Frontend
| 項目 | 技術 |
|------|------|
| フレームワーク | Vue 3 (Composition API) |
| 言語 | TypeScript |
| ビルドツール | Vite 6 |
| スタイリング | Tailwind CSS + PrimeVue |
| APIクライアント | Orval (自動生成) |
| 状態管理 | TanStack Query |
| HTTPクライアント | Axios |

## Database
| 環境 | 技術 |
|------|------|
| 開発環境 | SQLite |
| 本番環境 | MariaDB 10.5+ |

## Development
| 項目 | 技術 |
|------|------|
| テスト (Backend) | PHPUnit |
| テスト (Frontend) | Vitest |
| OpenAPIリンター | Redocly CLI |
