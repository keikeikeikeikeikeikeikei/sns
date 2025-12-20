<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require __DIR__ . '/config/database.php';

use App\Models\User;

try {
    if (!User::where('username', 'testuser')->exists()) {
        $user = new User();
        $user->username = 'testuser';
        $user->email = 'test@example.com';
        $user->password_hash = password_hash('password123', PASSWORD_DEFAULT);
        $user->display_name = 'Test User';
        $user->save();
        echo "User 'testuser' created successfully.\n";
    } else {
        echo "User 'testuser' already exists.\n";
    }
} catch (\Exception $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";
    exit(1);
}

