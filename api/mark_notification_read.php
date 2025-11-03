<?php
// Start output buffering to prevent any unwanted output
ob_start();

// Disable error display
ini_set('display_errors', 0);
error_reporting(0);

require_once '../config.php';

// Clean any output that may have been generated
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

    if (!isset($input['notification_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing notification_id']);
        exit;
    }

    $notification_id = (int)$input['notification_id'];
    $user_id = $session['user_id'];

    // Mark as read
    $result = $auth->markNotificationAsRead($notification_id, $user_id);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to mark notification as read']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

ob_end_flush();
