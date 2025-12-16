<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../AuthMiddleware.php';

class ReactionController
{
    private $db;
    private $auth;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->auth = new AuthMiddleware($this->db);
    }

    public function store($data)
    {
        $userId = $this->auth->authenticate();

        $postId = $data['post_id'] ?? null;
        $emoji = $data['emoji'] ?? '';

        if (!$postId || empty($emoji)) {
            http_response_code(400);
            return json_encode(['error' => 'Post ID and Emoji are required.']);
        }

        $check = $this->db->prepare("SELECT id FROM reactions WHERE user_id = :uid AND post_id = :pid AND emoji_char = :emoji");
        $check->execute([':uid' => $userId, ':pid' => $postId, ':emoji' => $emoji]);

        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $del = $this->db->prepare("DELETE FROM reactions WHERE id = :id");
            $del->execute([':id' => $existing['id']]);
            return json_encode(['message' => 'Reaction removed.']);
        }

        $query = "INSERT INTO reactions (user_id, post_id, emoji_char) VALUES (:uid, :pid, :emoji)";
        $stmt = $this->db->prepare($query);
        if ($stmt->execute([':uid' => $userId, ':pid' => $postId, ':emoji' => $emoji])) {
            return json_encode(['message' => 'Reaction added.']);
        }

        http_response_code(500);
        return json_encode(['error' => 'Failed to add reaction.']);
    }
}
