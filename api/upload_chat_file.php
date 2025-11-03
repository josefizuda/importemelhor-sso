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

    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded or upload error']);
        exit;
    }

    $conversation_id = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
    $message_type = $_POST['type'] ?? 'file';
    $message_text = $_POST['message'] ?? '';

    // Check if user is participant
    if (!$auth->isConversationParticipant($conversation_id, $session['user_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'You are not a participant of this conversation']);
        exit;
    }

    $file = $_FILES['file'];

    // Validate file type based on message type
    $allowed_types = [];
    switch ($message_type) {
        case 'image':
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            break;
        case 'audio':
            $allowed_types = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/webm'];
            break;
        default:
            // Allow most common file types
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/plain', 'application/zip', 'application/x-rar-compressed'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!empty($allowed_types) && !in_array($mime_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type for ' . $message_type]);
        exit;
    }

    // Validate file size (max 10MB)
    $max_size = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['error' => 'File size exceeds 10MB limit']);
        exit;
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'chat_' . time() . '_' . uniqid() . '.' . $extension;

    // Use absolute path
    $upload_dir = realpath(__DIR__ . '/..') . '/public/uploads/chat/';
    $upload_path = $upload_dir . $filename;

    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!@mkdir($upload_dir, 0777, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create upload directory']);
            exit;
        }
    }

    @chmod($upload_dir, 0777);

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save file']);
        exit;
    }

    @chmod($upload_path, 0644);

    // Prepare file data
    $file_url = '/public/uploads/chat/' . $filename;
    $file_data = [
        'url' => $file_url,
        'name' => $file['name'],
        'size' => $file['size'],
        'mime_type' => $mime_type,
        'metadata' => []
    ];

    // Add metadata for images
    if ($message_type === 'image') {
        $image_info = @getimagesize($upload_path);
        if ($image_info) {
            $file_data['metadata'] = [
                'width' => $image_info[0],
                'height' => $image_info[1]
            ];
        }
    }

    // Send message with file
    $result = $auth->sendMessage(
        $conversation_id,
        $session['user_id'],
        $message_text,
        $message_type,
        $file_data
    );

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => $result,
            'file_url' => $file_url
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
