<?php
require_once __DIR__ . '/backend/src/Database.php';

$db = new Database();
$conn = $db->getConnection();

$username = 'testuser';
$password = 'password123'; // The plain text password to verify

try {
    $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "User not found.\n";
        exit;
    }

    echo "User found: " . $user['username'] . "\n";
    echo "Stored hash: " . $user['password_hash'] . "\n";

    // Explicitly verify
    if (password_verify($password, $user['password_hash'])) {
        echo "Verification SUCCESS!\n";
    } else {
        echo "Verification FAILED!\n";
        // Let's reset it to be sure
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $update->execute([':hash' => $newHash, ':id' => $user['id']]);
        echo "Password reset to '$password' (Hash: $newHash)\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
