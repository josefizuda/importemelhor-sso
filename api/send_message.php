<?php
// Start output buffering
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once '../config.php';

ob_clean();
header('Content-Type: application/json');

$auth = new Auth();

try {
    // Check authentication
    if (!isset($_COOKIE['sso_token'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $session = $auth->validateSession($_COOKIE['sso_token']);
    if (!$session) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['conversation_id']) || !isset($input['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $conversation_id = (int)$input['conversation_id'];
    $message = trim($input['message']);
    $message_type = $input['message_type'] ?? 'text';
    $reply_to = isset($input['reply_to']) ? (int)$input['reply_to'] : null;

    // Check if user is participant
    if (!$auth->isConversationParticipant($conversation_id, $session['user_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'You are not a participant of this conversation']);
        exit;
    }

    // Send message
    $result = $auth->sendMessage(
        $conversation_id,
        $session['user_id'],
        $message,
        $message_type,
        null,
        $reply_to
    );

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => $result
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

ob_end_flush();
