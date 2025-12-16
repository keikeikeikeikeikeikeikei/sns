<?php

require_once __DIR__ . '/../Database.php';

class UserController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function register($data)
    {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $displayName = $data['display_name'] ?? $username;

        if (empty($username) || empty($password)) {
            http_response_code(400);
            return json_encode(['error' => 'Username and password are required.']);
        }

        // Check if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existingUser) {
            http_response_code(409);
            return json_encode(['error' => 'Username already exists.']);
        }

        // Insert new user (Note: Password hashing should be used in production!)
        // For this demo/MVP, we'll use simple hashing if possible, or just plain text if environment is unknown/restricted? 
        // No, always use password_hash in PHP.
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (username, display_name, password_hash) VALUES (:username, :display_name, :password)";
        // Wait, schema didn't have password_hash! I need to update schema or assume 'password' column.
        // Let's check schema.sql. I missed adding a password column! 
        // I will fix schema.sql in a separate step or just assume I need to alter it.
        // For now let's write the code assuming I'll fix the schema.

        $query = "INSERT INTO users (username, display_name, password_hash) VALUES (:username, :display_name, :password)";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':display_name', $displayName);
        $stmt->bindParam(':password', $passwordHash); // Store hash

        if ($stmt->execute()) {
            http_response_code(201);
            return json_encode(['message' => 'User registered successfully.']);
        } else {
            http_response_code(500);
            return json_encode(['error' => 'Registration failed.']);
        }
    }

    public function login($data)
    {
        error_log("Login attempt: " . print_r($data, true));
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $stmt = $this->db->prepare("SELECT id, username, password_hash FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            error_log("User found: " . $row['username']);
            error_log("Stored Hash: " . $row['password_hash']);
            error_log("Provided Password: " . $password);

            if (password_verify($password, $row['password_hash'])) {
                error_log("Password verify success");
                // Generate simple token (for MVP)
                $token = bin2hex(random_bytes(16));

                // Save token to DB
                $update = $this->db->prepare("UPDATE users SET api_token = :token WHERE id = :id");
                $update->execute([':token' => $token, ':id' => $row['id']]);

                return json_encode([
                    'message' => 'Login successful.',
                    'token' => $token,
                    'user' => [
                        'id' => $row['id'],
                        'username' => $row['username']
                    ]
                ]);
            } else {
                error_log("Password verify failed");
            }
        } else {
            error_log("User not found: " . $username);
        }

        http_response_code(401);

        $errorDetail = 'Invalid credentials.';
        if (!$row) {
            $errorDetail = "User not found: $username"; // For debugging only!
        } else {
            $errorDetail = "Password mismatch for user: $username"; // For debugging only!
        }

        return json_encode(['error' => $errorDetail]);
    }
}
