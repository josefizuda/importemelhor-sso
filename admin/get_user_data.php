<?php
require_once '../config.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check authentication
if (!isset($_COOKIE['sso_token'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$session = $auth->validateSession($_COOKIE['sso_token']);
if (!$session) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid session']);
    exit;
}

// Check admin permission
if (!$auth->isAdmin($session['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Get user ID from query parameter
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT id, email, name, job_title, department, is_active, can_access_chat, role_id
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    echo json_encode($user);

} catch (PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
