<?php

declare(strict_types=1);

/**
 * Database Migration Script
 * Run: php database/migrate.php
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

require __DIR__ . '/../config/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$schema = Capsule::schema();

echo "Running migrations...\n";

// Users table
if (!$schema->hasTable('users')) {
    $schema->create('users', function ($table) {
        $table->id();
        $table->string('username', 50)->unique();
        $table->string('email', 255)->unique();
        $table->string('password_hash', 255);
        $table->string('display_name', 255)->nullable();
        $table->text('bio')->nullable();
        $table->string('avatar_url', 255)->nullable();
        $table->timestamps();
    });
    echo "Created: users\n";
}

// Posts table (unified: feed, qa, blog)
if (!$schema->hasTable('posts')) {
    $schema->create('posts', function ($table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->enum('type', ['feed', 'qa', 'blog']);
        $table->string('content_short', 150)->nullable(); // For feeds
        $table->text('content_long')->nullable(); // For Q&A, Blog
        $table->string('title', 255)->nullable(); // For Q&A, Blog
        $table->enum('qa_status', ['open', 'resolved'])->nullable(); // For Q&A
        $table->unsignedBigInteger('best_answer_id')->nullable(); // For Q&A
        $table->unsignedBigInteger('parent_post_id')->nullable(); // For Q&A answers
        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->index(['type', 'created_at']);
        $table->index('parent_post_id');
    });
    echo "Created: posts\n";
}

// Quotes table (polymorphic quoting)
if (!$schema->hasTable('quotes')) {
    $schema->create('quotes', function ($table) {
        $table->id();
        $table->unsignedBigInteger('source_post_id'); // 引用元
        $table->unsignedBigInteger('quoting_post_id'); // 引用先（新しい投稿）
        $table->timestamp('created_at')->useCurrent();

        $table->foreign('source_post_id')->references('id')->on('posts')->onDelete('cascade');
        $table->foreign('quoting_post_id')->references('id')->on('posts')->onDelete('cascade');
        $table->unique(['source_post_id', 'quoting_post_id']);
    });
    echo "Created: quotes\n";
}

// Reactions table (emoji reactions)
if (!$schema->hasTable('reactions')) {
    $schema->create('reactions', function ($table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('post_id');
        $table->string('emoji', 32); // Unicode emoji
        $table->timestamp('created_at')->useCurrent();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
        $table->unique(['user_id', 'post_id', 'emoji']); // 1 emoji per user per post
        $table->index(['post_id', 'emoji']);
    });
    echo "Created: reactions\n";
}

echo "Migrations completed!\n";
