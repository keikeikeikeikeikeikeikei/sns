<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../AuthMiddleware.php';

class PostController
{
    private $db;
    private $auth;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->auth = new AuthMiddleware($this->db);
    }

    public function index($type = null)
    {
        $userId = $this->auth->authenticate(); // Capture User ID

        $query = "SELECT p.*, u.username, u.display_name,
                  q.quoted_post_id,
                  qp.content as quoted_content, qp.type as quoted_type, qp.title as quoted_title, qp.image_path as quoted_image_path,
                  qu.username as quoted_username, qu.display_name as quoted_display_name
                  FROM posts p 
                  JOIN users u ON p.user_id = u.id
                  LEFT JOIN quotes q ON p.id = q.quoter_post_id
                  LEFT JOIN posts qp ON q.quoted_post_id = qp.id
                  LEFT JOIN users qu ON qp.user_id = qu.id";

        $whereConditions = [];
        $params = [];

        if ($type) {
            $whereConditions[] = "p.type = :type";
            $params[':type'] = $type;
        }

        $replyToId = isset($_GET['reply_to_id']) ? $_GET['reply_to_id'] : null;
        if ($replyToId) {
            $whereConditions[] = "p.reply_to_id = :reply_to_id";
            $params[':reply_to_id'] = $replyToId;
        }

        $id = isset($_GET['id']) ? $_GET['id'] : null;
        if ($id) {
            $whereConditions[] = "p.id = :id";
            $params[':id'] = $id;
        }

        // Exclude answers from main feed unless specifically requested
        if (!$replyToId && !$id && $type !== 'answer') {
            $whereConditions[] = "p.type != 'answer'";
        }

        $search = isset($_GET['q']) ? trim($_GET['q']) : null;
        if ($search) {
            $whereConditions[] = "(p.content LIKE :search OR p.title LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }

        $query .= " ORDER BY p.created_at DESC";

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($limit < 1 || $limit > 100)
            $limit = 20;
        if ($page < 1)
            $page = 1;
        $offset = ($page - 1) * $limit;

        $query .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $posts = $stmt->fetchAll();

        if (!empty($posts)) {
            $postIds = array_column($posts, 'id');
            $idsStr = implode(',', array_map('intval', $postIds));

            // Fetch counts
            $rQuery = "SELECT r.post_id, r.emoji_char, count(*) as count 
                       FROM reactions r 
                       WHERE r.post_id IN ($idsStr) 
                       GROUP BY r.post_id, r.emoji_char";
            $rStmt = $this->db->query($rQuery);
            $reactions = $rStmt->fetchAll(PDO::FETCH_GROUP); // [pid => [[emoji, count], ...]]

            // Fetch user's own reactions
            $myQuery = "SELECT post_id, emoji_char FROM reactions WHERE user_id = ? AND post_id IN ($idsStr)";
            $mStmt = $this->db->prepare($myQuery);
            $mStmt->execute([$userId]);
            $myReactions = $mStmt->fetchAll(PDO::FETCH_GROUP); // [pid => [[emoji, ...], ...]]

            foreach ($posts as &$post) {
                $pid = $post['id'];
                $postReactions = isset($reactions[$pid]) ? $reactions[$pid] : [];

                // Map to check if I reacted
                $myEmojis = [];
                if (isset($myReactions[$pid])) {
                    foreach ($myReactions[$pid] as $mr) {
                        $myEmojis[] = $mr['emoji_char'];
                    }
                }

                foreach ($postReactions as &$pr) {
                    $pr['is_me'] = in_array($pr['emoji_char'], $myEmojis);
                }
                $post['reactions'] = $postReactions;
            }
        }

        return json_encode($posts);
    }

    public function store($data)
    {
        $userId = $this->auth->authenticate();

        $type = $data['type'] ?? 'microblog';
        $content = trim($data['content'] ?? '');
        $title = trim($data['title'] ?? '');
        $quotedPostId = $data['quoted_post_id'] ?? null;
        $replyToId = $data['reply_to_id'] ?? null; // Capture reply_to_id

        // Handle Image Upload with Privacy (Strip Metadata)
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileMime = mime_content_type($fileTmpPath);

            if (!in_array($fileMime, $allowedMimes)) {
                http_response_code(400);
                return json_encode(['error' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.']);
            }

            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                http_response_code(400);
                return json_encode(['error' => 'File too large. Max 5MB.']);
            }

            // Generate secure filename
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array(strtolower($ext), $allowedExts)) {
                $ext = 'jpg';
            }
            $newFileName = bin2hex(random_bytes(16)) . '.' . $ext;

            $uploadDir = __DIR__ . '/../../public/uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $destPath = $uploadDir . '/' . $newFileName;

            // Process image to strip metadata if GD is available
            $success = false;

            // Check if GD is installed
            $gdAvailable = extension_loaded('gd') && function_exists('imagecreatefromjpeg');

            if ($gdAvailable) {
                try {
                    // Create image resource from uploaded file
                    $srcImage = null;
                    switch ($fileMime) {
                        case 'image/jpeg':
                            $srcImage = imagecreatefromjpeg($fileTmpPath);
                            break;
                        case 'image/png':
                            $srcImage = imagecreatefrompng($fileTmpPath);
                            break;
                        case 'image/gif':
                            $srcImage = imagecreatefromgif($fileTmpPath);
                            break;
                        case 'image/webp':
                            $srcImage = imagecreatefromwebp($fileTmpPath);
                            break;
                    }

                    if ($srcImage) {
                        // Handle transparency for PNG/WEBP
                        if ($fileMime == 'image/png' || $fileMime == 'image/webp') {
                            imagepalettetotruecolor($srcImage);
                            imagealphablending($srcImage, true);
                            imagesavealpha($srcImage, true);
                        }

                        // Save processed image (this strips EXIF)
                        switch ($fileMime) {
                            case 'image/jpeg':
                                $success = imagejpeg($srcImage, $destPath, 85);
                                break; // 85% quality
                            case 'image/png':
                                $success = imagepng($srcImage, $destPath, 9);
                                break;
                            case 'image/gif':
                                $success = imagegif($srcImage, $destPath);
                                break;
                            case 'image/webp':
                                $success = imagewebp($srcImage, $destPath, 85);
                                break;
                        }

                        imagedestroy($srcImage);
                    } else {
                        // GD failed to open image
                        error_log("GD failed to create image from source. Falling back to simple move.");
                        $success = move_uploaded_file($fileTmpPath, $destPath);
                    }
                } catch (Exception $e) {
                    error_log("Image processing error: " . $e->getMessage());
                    $success = move_uploaded_file($fileTmpPath, $destPath);
                } catch (Error $e) {
                    error_log("Image processing fatal error: " . $e->getMessage());
                    $success = move_uploaded_file($fileTmpPath, $destPath);
                }
            } else {
                error_log("GD extension not loaded. Skipping metadata removal.");
                $success = move_uploaded_file($fileTmpPath, $destPath);
            }

            if ($success) {
                $imagePath = '/uploads/' . $newFileName;
            } else {
                http_response_code(500);
                return json_encode(['error' => 'Failed to process uploaded file.']);
            }
        }

        $allowedTypes = ['microblog', 'blog', 'question', 'answer'];
        if (!in_array($type, $allowedTypes)) {
            http_response_code(400);
            return json_encode(['error' => 'Invalid post type.']);
        }

        if (empty($content) && empty($imagePath)) {
            http_response_code(400);
            return json_encode(['error' => 'Content or Image is required.']);
        }

        if ($type === 'microblog' && mb_strlen($content) > 150) {
            http_response_code(400);
            return json_encode(['error' => 'Microblog posts cannot exceed 150 characters.']);
        }
        if (($type === 'blog' || $type === 'question' || $type === 'answer') && mb_strlen($content) > 10000) {
            http_response_code(400);
            return json_encode(['error' => 'Content cannot exceed 10,000 characters.']);
        }
        if (mb_strlen($title) > 255) {
            http_response_code(400);
            return json_encode(['error' => 'Title cannot exceed 255 characters.']);
        }

        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO posts (user_id, type, content, title, image_path, reply_to_id, created_at) VALUES (:user_id, :type, :content, :title, :image_path, :reply_to_id, datetime('now'))";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $userId,
                ':type' => $type,
                ':content' => $content,
                ':title' => $title,
                ':image_path' => $imagePath,
                ':reply_to_id' => $replyToId // Bind reply_to_id
            ]);
            $postId = $this->db->lastInsertId();

            if ($quotedPostId) {
                $qQuery = "INSERT INTO quotes (quoter_post_id, quoted_post_id) VALUES (:quoter, :quoted)";
                $qStmt = $this->db->prepare($qQuery);
                $qStmt->execute([':quoter' => $postId, ':quoted' => $quotedPostId]);
            }

            $this->db->commit();

            http_response_code(201);
            return json_encode(['message' => 'Post created successfully.', 'id' => $postId]);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return json_encode(['error' => 'Failed to create post: ' . $e->getMessage()]);
        }
    }


    public function setBestAnswer()
    {
        $userId = $this->auth->authenticate();
        $data = json_decode(file_get_contents('php://input'), true);

        $questionId = $data['question_id'] ?? null;
        $answerId = $data['answer_id'] ?? null;

        if (!$questionId || !$answerId) {
            http_response_code(400);
            echo json_encode(['error' => 'Question ID and Answer ID are required.']);
            return;
        }

        // Verify Question Ownership
        $qStmt = $this->db->prepare("SELECT user_id, type FROM posts WHERE id = :id");
        $qStmt->execute([':id' => $questionId]);
        $question = $qStmt->fetch(PDO::FETCH_ASSOC);

        if (!$question) {
            http_response_code(404);
            echo json_encode(['error' => 'Question not found.']);
            return;
        }

        if ($question['user_id'] != $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized.']);
            return;
        }

        if ($question['type'] !== 'question') {
            http_response_code(400);
            echo json_encode(['error' => 'Post is not a question.']);
            return;
        }

        // Verify Answer belongs to Question
        $aStmt = $this->db->prepare("SELECT reply_to_id, type FROM posts WHERE id = :id");
        $aStmt->execute([':id' => $answerId]);
        $answer = $aStmt->fetch(PDO::FETCH_ASSOC);

        if (!$answer || $answer['reply_to_id'] != $questionId) {
            http_response_code(400);
            echo json_encode(['error' => 'Answer does not belong to this question.']);
            return;
        }

        // Update
        $uStmt = $this->db->prepare("UPDATE posts SET best_answer_id = :aid WHERE id = :qid");
        $uStmt->execute([':aid' => $answerId, ':qid' => $questionId]);

        echo json_encode(['message' => 'Best answer set successfully.']);
    }
}
