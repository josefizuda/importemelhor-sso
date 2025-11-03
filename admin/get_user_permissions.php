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
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check admin permission
$isAdmin = ($auth->isAdmin($session['user_id']));
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Get user_id from query string
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id']);
    exit;
}

// Get user app access
$apps = $auth->getUserAppAccess($userId);

echo json_encode($apps);
