<?php

class AuthMiddleware
{
    private $db;

    public function __construct($dbConnection)
    {
        $this->db = $dbConnection;
    }

    public function authenticate()
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: No token provided']);
            exit;
        }

        $token = $matches[1];

        $stmt = $this->db->prepare("SELECT id FROM users WHERE api_token = :token");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Invalid token']);
            exit;
        }

        return $user['id'];
    }
}
