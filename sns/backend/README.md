# SNS Backend (PHP)

## Setup

1.  Requires PHP with SQLite extension enabled.
2.  The database `sns_debug.db` will be automatically created in the project root.
3.  Run the initialization script if needed (though the app may handle this, strictly speaking `schema.sql` can be imported via `init_db.php` if available).
    *   Command: `php init_db.php` (from the project root)

## Running Locally

You can use the built-in PHP server for testing:

```bash
cd public
php -S localhost:8000
```

The API will be available at `http://localhost:8000`.

## API Endpoints

- `GET /` or `GET /api`: Health check.
