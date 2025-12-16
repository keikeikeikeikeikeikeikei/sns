<?php
require_once __DIR__ . '/backend/src/Database.php';

$db = new Database();
$conn = $db->getConnection();

$username = 'testuser';
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);
$displayName = 'Test User';

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);

    if ($stmt->fetch()) {
        echo "User '$username' already exists.\n";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, display_name, password_hash) VALUES (:username, :display_name, :password)");
        $stmt->execute([
            ':username' => $username,
            ':display_name' => $displayName,
            ':password' => $hash
        ]);
        echo "User '$username' created successfully.\n";
    }

    echo "Password: $password\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
