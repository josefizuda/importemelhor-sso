<?php
header('Content-Type: application/json');

require_once '../config.php';

$auth = new Auth();

try {
    // Check authentication
    if (!isset($_COOKIE['sso_token'])) {
        echo json_encode(['count' => 0]);
        exit;
    }

    $session = $auth->validateSession($_COOKIE['sso_token']);

    if (!$session) {
        echo json_encode(['count' => 0]);
        exit;
    }

    // Get unread count
    $count = $auth->getTotalUnreadMessagesCount($session['user_id']);

    echo json_encode([
        'count' => $count,
        'success' => true
    ]);

} catch (Exception $e) {
    echo json_encode([
        'count' => 0,
        'error' => $e->getMessage()
    ]);
}
