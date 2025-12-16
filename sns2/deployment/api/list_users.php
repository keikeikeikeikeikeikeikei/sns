<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/database.php';

$users = App\Models\User::all(['id', 'username', 'email', 'display_name']);
echo "=== 登録済みユーザー ===\n";
foreach ($users as $user) {
    echo "ID: {$user->id}\n";
    echo "Username: {$user->username}\n";
    echo "Email: {$user->email}\n";
    echo "Display Name: {$user->display_name}\n";
    echo "---\n";
}
if ($users->isEmpty()) {
    echo "ユーザーがいません。\n";
}
